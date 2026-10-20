<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * One booted kernel serving several requests, as FrankenPHP worker mode does: nothing may leak between them.
 * The container-level counterpart of what the Doctrine bundle checks with SQLite.
 */
final class WorkerModeTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = TestKernel::create(
            ['posts' => [
                'provider' => 'in_memory',
                'options' => ['source' => PostsSource::class],
                'pagination' => ['per_page' => 2, 'max_per_page' => 10],
                'filters' => [
                    'title' => ['type' => 'text'],
                    'status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published', 'archived']], 'default' => 'published', 'locked' => true, 'providers' => ['in_memory']],
                    'sort' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id', 'title' => 'title'], 'default' => [['column' => 'id', 'direction' => 'asc']]]],
                ],
            ]],
            static fn (ContainerConfigurator $c) => $c->services()->defaults()->autoconfigure()->set(PostsSource::class),
        );
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    private function registry(): QueryHandlerRegistry
    {
        return $this->kernel->getContainer()->get(QueryHandlerRegistry::class);
    }

    /**
     * Simulates one request: a fresh handler, then the services reset the worker does between requests.
     *
     * @param array<string, mixed> $query
     *
     * @return array{ids: list<int>, page: int, total: int|null}
     */
    private function request(array $query): array
    {
        $result = $this->registry()->createQueryHandler('posts')->handle(ListInput::fromArray($query));
        $this->kernel->getContainer()->get('services_resetter')->reset();

        return ['ids' => array_column($result->items(), 'id'), 'page' => $result->page(), 'total' => $result->total()];
    }

    public function testConsecutiveRequestsAreIsolated(): void
    {
        // The locked filter keeps drafts out: published posts are 1, 3, 4.
        self::assertSame(['ids' => [1, 3], 'page' => 1, 'total' => 3], $this->request([]));
        self::assertSame(['ids' => [4], 'page' => 2, 'total' => 3], $this->request(['page' => 2]));
        self::assertSame(['ids' => [3], 'page' => 1, 'total' => 1], $this->request(['filters' => ['title' => 'über']]));
        self::assertSame(['ids' => [1, 3], 'page' => 1, 'total' => 3], $this->request([]), 'the previous request left no filter, page or sort behind');
        self::assertSame(['ids' => [4, 3], 'page' => 1, 'total' => 3], $this->request(['sort' => '-id']));
        self::assertSame(['ids' => [1, 3], 'page' => 1, 'total' => 3], $this->request([]));
    }

    public function testEveryCallCreatesANewHandler(): void
    {
        $registry = $this->registry();

        self::assertNotSame($registry->createQueryHandler('posts'), $registry->createQueryHandler('posts'));
        self::assertSame($registry->getDefinition('posts'), $registry->getDefinition('posts'), 'definitions are immutable and shared');
    }

    public function testTwoHandlersForTheSameListInOneRequestShareNoState(): void
    {
        $first = $this->registry()->createQueryHandler('posts');
        $second = $this->registry()->createQueryHandler('posts');

        $filtered = $first->handle(ListInput::fromArray(['filters' => ['title' => 'zoë'], 'per_page' => 1]));
        $all = $second->handle(new ListInput());

        self::assertSame([4], array_column($filtered->items(), 'id'));
        self::assertSame(1, $filtered->perPage());
        self::assertSame([1, 3], array_column($all->items(), 'id'));
        self::assertSame(2, $all->perPage());
        self::assertSame(3, $all->total());
    }

    public function testAHandlerIsSingleUse(): void
    {
        $handler = $this->registry()->createQueryHandler('posts');
        $handler->handle(new ListInput());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('single-use');

        $handler->handle(new ListInput());
    }
}
