<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Http;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Http\ListRequestParser;
use Sidus\FilterBundle\Http\ListResponseFactory;
use Sidus\FilterBundle\Http\ListResultNormalizer;
use Sidus\FilterBundle\Input\Violation;
use Sidus\FilterBundle\Input\ViolationList;
use Sidus\FilterBundle\Result\ListResult;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsController;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class HttpAdapterTest extends TestCase
{
    public function testParsesTheQueryString(): void
    {
        $input = new ListRequestParser()->parse(Request::create('/posts?filters[title]=x&filters[tags][]=a&sort=-date&page=2&per_page=50'));

        self::assertSame(['title' => 'x', 'tags' => ['a'], 'sort' => '-date'], $input->filters);
        self::assertSame('2', $input->page);
        self::assertSame('50', $input->perPage);
    }

    public function testTheSortValueFeedsTheListsSortFilterWhateverItsName(): void
    {
        $definition = new ListDefinition('l', 'p', filters: [new FilterDefinition('order', 'sort', name: 'o')]);

        $input = new ListRequestParser()->parse(Request::create('/?sort=-id'), $definition);

        self::assertSame(['o' => '-id'], $input->filters);
    }

    public function testConfigurableKeysAndTopLevelFilters(): void
    {
        $parser = new ListRequestParser('', 'order', 'p', 'size');

        $input = $parser->parse(Request::create('/?title=x&status=a&order=-id&p=3&size=7&sort=ignored-as-filter'));

        self::assertSame(['title' => 'x', 'status' => 'a', 'sort' => 'ignored-as-filter', 'sort' => 'ignored-as-filter'] + ['sort' => '-id'], $input->filters);
        self::assertSame('3', $input->page);
        self::assertSame('7', $input->perPage);
    }

    public function testAJsonBodyUsesTheSameStructureAndWinsOverTheQuery(): void
    {
        $request = Request::create('/posts?page=1&filters[a]=q', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['filters' => ['b' => ['operator' => 'gt', 'value' => 3]], 'sort' => 'x', 'page' => 4]));

        $input = new ListRequestParser()->parse($request);

        self::assertSame(['b' => ['operator' => 'gt', 'value' => 3], 'sort' => 'x'], $input->filters, 'the body replaces the query filters');
        self::assertSame(4, $input->page);
    }

    public function testMalformedJsonIsABadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);

        new ListRequestParser()->parse(Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{nope'));
    }

    public function testANonObjectJsonBodyIsABadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);

        new ListRequestParser()->parse(Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '"x"'));
    }

    public function testNormalizerShapesItemsAndMeta(): void
    {
        $result = new ListResult([['id' => 1], ['id' => 2]], 2, 2, 5, new ViolationList());

        $body = new ListResultNormalizer(static fn (array $item): int => $item['id'])->normalize($result);

        self::assertSame([1, 2], $body['items']);
        self::assertSame(['page' => 2, 'per_page' => 2, 'total' => 5, 'pages' => 3, 'next' => 3], $body['meta']);
        self::assertSame([['id' => 1], ['id' => 2]], new ListResultNormalizer()->normalize($result)['items']);
        self::assertSame(['a' => 1], new ListResultNormalizer()->normalize($result, static fn (array $i): array => ['a' => 1])['items'][0]);
    }

    public function testNormalizerWithAnUnknownTotalAndTheLastPage(): void
    {
        $unknown = new ListResultNormalizer()->normalize(new ListResult([1], 1, 1, null, new ViolationList(), hasNextPage: true));
        $last = new ListResultNormalizer()->normalize(new ListResult([1], 3, 1, 3, new ViolationList()));

        self::assertSame(['page' => 1, 'per_page' => 1, 'total' => null, 'pages' => null, 'next' => 2], $unknown['meta']);
        self::assertNull($last['meta']['next']);
    }

    public function testStrictByDefaultAnswersAProblemJsonWithoutTheSubmittedValues(): void
    {
        $result = new ListResult([], 1, 10, 0, new ViolationList(new Violation('filters.views.value', 'Expected a number.', 'secret')));

        $response = new ListResponseFactory()->create($result);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame(
            ['type' => 'about:blank', 'title' => 'Invalid list parameters', 'status' => 400, 'violations' => [['path' => 'filters.views.value', 'message' => 'Expected a number.']]],
            json_decode((string) $response->getContent(), true),
        );
    }

    public function testNonStrictAnswersTheDefaultsWithTheViolations(): void
    {
        $result = new ListResult([1], 1, 10, 1, new ViolationList(new Violation('page', 'Expected a positive integer.')));

        $response = new ListResponseFactory(strict: false)->create($result);
        $strictOverride = new ListResponseFactory(strict: false)->create($result, strict: true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([['path' => 'page', 'message' => 'Expected a positive integer.']], json_decode((string) $response->getContent(), true)['violations']);
        self::assertSame(400, $strictOverride->getStatusCode());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function kernel(array $config = []): TestKernel
    {
        $kernel = TestKernel::create(
            ['posts' => [
                'provider' => 'in_memory',
                'options' => ['source' => PostsSource::class],
                'pagination' => ['per_page' => 2, 'max_per_page' => 5],
                'filters' => [
                    'views' => ['type' => 'number', 'options' => ['operators' => ['eq', 'gt']]],
                    'title' => ['type' => 'text'],
                    'order' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id', 'views' => 'views']]],
                ],
            ]],
            static function (ContainerConfigurator $c): void {
                $services = $c->services()->defaults()->autowire()->autoconfigure();
                $services->set(PostsSource::class);
                $services->set(PostsController::class);
            },
            config: $config,
            routes: static fn (RoutingConfigurator $routes) => $routes->add('posts', '/posts')->controller(PostsController::class),
        );
        $kernel->boot();

        return $kernel;
    }

    public function testEndToEndThroughTheKernelWithTheArgumentResolver(): void
    {
        $kernel = $this->kernel();

        $ok = $kernel->handle(Request::create('/posts?filters[title]=hello&sort=-id'), catch: false);
        $paged = $kernel->handle(Request::create('/posts?page=2&per_page=2'), catch: false);
        $bad = $kernel->handle(Request::create('/posts?filters[views][operator]=gt&filters[views][value]=abc&per_page=99'), catch: false);
        $json = $kernel->handle(Request::create('/posts', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"filters":{"views":{"operator":"gt","value":50}},"sort":"views"}'), catch: false);

        self::assertSame(200, $ok->getStatusCode());
        self::assertSame(['items' => [['id' => 2, 'title' => 'hello again'], ['id' => 1, 'title' => 'Hello World']], 'meta' => ['page' => 1, 'per_page' => 2, 'total' => 2, 'pages' => 1, 'next' => null]], json_decode((string) $ok->getContent(), true));
        self::assertSame([3, 4], array_column(json_decode((string) $paged->getContent(), true)['items'], 'id'));
        self::assertSame(400, $bad->getStatusCode());
        self::assertSame(['per_page', 'filters.views.value'], array_column(json_decode((string) $bad->getContent(), true)['violations'], 'path'));
        self::assertSame([1, 4], array_column(json_decode((string) $json->getContent(), true)['items'], 'id'), 'views > 50 sorted by views then id; 6 is on page 2');
        $kernel->shutdown();
    }

    public function testConfiguredKeysAndNonStrictMode(): void
    {
        $kernel = $this->kernel(['http' => ['filters_key' => '', 'strict' => false]]);

        $response = $kernel->handle(Request::create('/posts?title=hello&page=zero'), catch: false);
        $body = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([1, 2], array_column($body['items'], 'id'));
        self::assertSame([['path' => 'page', 'message' => 'Expected a positive integer.']], $body['violations']);
        $kernel->shutdown();
    }
}
