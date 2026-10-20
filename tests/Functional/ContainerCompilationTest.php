<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Exception\UnknownListException;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\BareFactory;
use Sidus\FilterBundle\Tests\Support\EvenIdBuilder;
use Sidus\FilterBundle\Tests\Support\FixedCount;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Sidus\FilterBundle\Tests\Support\SecondBareFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ContainerCompilationTest extends TestCase
{
    /**
     * @param array<string, mixed> $list
     *
     * @return array<string, array<string, mixed>>
     */
    private static function posts(array $list = []): array
    {
        return ['posts' => $list + ['provider' => 'in_memory', 'options' => ['source' => PostsSource::class]]];
    }

    private static function services(): \Closure
    {
        return static function (ContainerConfigurator $c): void {
            $services = $c->services()->defaults()->autoconfigure();
            $services->set(PostsSource::class);
            $services->set(BareFactory::class);
            $services->set(EvenIdBuilder::class);
            $services->set(FixedCount::class);
            $services->set('app.not_a_strategy', \stdClass::class);
        };
    }

    /**
     * @param array<string, array<string, mixed>> $lists
     */
    private function boot(array $lists, ?\Closure $services = null, ?string $id = null): TestKernel
    {
        $kernel = TestKernel::create($lists, $services ?? self::services(), $id);
        $kernel->boot();

        return $kernel;
    }

    public function testCompilesWithZeroDeprecationsAndExposesTheRegistries(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED | \E_DEPRECATED);
        try {
            $kernel = $this->boot(self::posts([
                'filters' => [
                    'title' => ['type' => 'text'],
                    'sort' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id']]],
                ],
            ]));
            $container = $kernel->getContainer();
            $registry = $container->get(QueryHandlerRegistry::class);
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
        self::assertTrue($registry->has('posts'));
        self::assertFalse($registry->has('nope'));
        self::assertSame(['posts'], $registry->codes());
        self::assertSame(['posts'], $container->get(ListDefinitionRegistry::class)->codes());
        self::assertSame('in_memory', $registry->getDefinition('posts')->provider);
        $kernel->shutdown();
    }

    public function testUnknownListIsReportedWithTheAvailableCodes(): void
    {
        $kernel = $this->boot(self::posts());

        $this->expectException(UnknownListException::class);
        $this->expectExceptionMessage('Unknown list "nope", available: posts.');

        try {
            $kernel->getContainer()->get(QueryHandlerRegistry::class)->createQueryHandler('nope');
        } finally {
            $kernel->shutdown();
        }
    }

    public function testTheProviderMayBeAParameter(): void
    {
        $kernel = $this->boot(
            self::posts(['provider' => '%app.list_provider%']),
            static function (ContainerConfigurator $c): void {
                $c->parameters()->set('app.list_provider', 'in_memory');
                $c->services()->defaults()->autoconfigure()->set(PostsSource::class);
            },
        );

        self::assertSame('in_memory', $kernel->getContainer()->get(QueryHandlerRegistry::class)->getDefinition('posts')->provider);
        $kernel->shutdown();
    }

    public function testTheDumpedContainerWorksWithoutRecompiling(): void
    {
        $lists = self::posts(['filters' => [
            'title' => ['type' => 'text'],
            'sort' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id'], 'default' => [['column' => 'id', 'direction' => 'desc']]]],
        ]]);
        $first = TestKernel::create($lists, self::services(), 'dump-test');
        $first->boot();
        $first->shutdown();

        $second = TestKernel::create($lists, self::services(), 'dump-test', wipeCache: false);
        $second->boot();
        $result = $second->getContainer()->get(QueryHandlerRegistry::class)->createQueryHandler('posts')->handle(ListInput::fromArray(['per_page' => 3]));

        self::assertSame([6, 5, 4], array_column($result->items(), 'id'));
        self::assertSame(6, $result->total());
        $second->shutdown();
    }

    public function testNativeFilterBuilderAndCountServiceAreWiredByAutoconfiguration(): void
    {
        $kernel = $this->boot(self::posts([
            'count' => FixedCount::class,
            'filters' => [
                'parity' => ['type' => 'native', 'options' => ['builder' => EvenIdBuilder::class], 'providers' => ['in_memory']],
                'sort' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id']]],
            ],
        ]));

        $result = $kernel->getContainer()->get(QueryHandlerRegistry::class)->createQueryHandler('posts')
            ->handle(ListInput::fromArray(['filters' => ['parity' => 'even']]));

        self::assertSame([2, 4, 6], array_column($result->items(), 'id'));
        self::assertSame(777, $result->total());
        $kernel->shutdown();
    }

    public function testProviderRestrictedFiltersAreIgnoredForOtherProviders(): void
    {
        $kernel = $this->boot(self::posts(['filters' => [
            'title_ft' => ['name' => 'title', 'type' => 'no_such_type', 'providers' => ['elasticsearch']],
            'title' => ['type' => 'text', 'providers' => ['in_memory']],
        ]]));

        $definition = $kernel->getContainer()->get(QueryHandlerRegistry::class)->getDefinition('posts');

        self::assertSame(['title'], array_map(static fn ($f) => $f->key, $definition->filters));
        $kernel->shutdown();
    }

    /**
     * @param array<string, array<string, mixed>> $lists
     */
    #[DataProvider('misconfigurations')]
    public function testMisconfigurationsFailAtCompileTimeWithAClearMessage(array $lists, string $message, ?\Closure $services = null): void
    {
        $kernel = TestKernel::create($lists, $services ?? self::services());

        try {
            $kernel->boot();
            self::fail('The container should not compile.');
        } catch (ConfigurationException $e) {
            self::assertStringContainsString($message, $e->getMessage());
        }
    }

    public static function misconfigurations(): iterable
    {
        yield 'unknown provider' => [self::posts(['provider' => 'nope']), 'List "posts": unknown provider "nope" (the provider must be known at compile time), available: bare, in_memory'];
        yield 'unknown filter type' => [self::posts(['filters' => ['t' => ['type' => 'nope']]]), 'List "posts", filter "t": Unknown filter type "nope"'];
        yield 'bad list options' => [self::posts(['options' => ['source' => PostsSource::class, 'oops' => 1]]), 'List "posts": options: The option "oops" does not exist'];
        yield 'missing list option' => [self::posts(['options' => []]), 'The required option "source" is missing'];
        yield 'bad filter options' => [self::posts(['filters' => ['t' => ['type' => 'text', 'options' => ['operators' => ['regex']]]]]), 'filter "t" (type "text"): options:'];
        yield 'bad attribute' => [self::posts(['filters' => ['t' => ['type' => 'text', 'attributes' => ['a; b']]]]), 'Invalid field path "a; b"'];
        yield 'locked without default' => [self::posts(['filters' => ['t' => ['type' => 'text', 'locked' => true]]]), 'a locked filter requires a "default"'];
        yield 'duplicate public name' => [self::posts(['filters' => ['a' => ['type' => 'text'], 'b' => ['type' => 'text', 'name' => 'a']]]), 'filters "a" and "b" have the same public name "a"'];
        yield 'unsupported criterion' => [
            self::posts(['provider' => 'bare', 'options' => [], 'filters' => ['t' => ['type' => 'text']]]),
            'criterion Sidus\FilterBundle\Criterion\Contains is not supported: provider "bare" has no compiler for it',
        ];
        yield 'count service does not exist' => [self::posts(['count' => 'app.nope']), 'count "app.nope" is neither "auto", "none" nor an existing service id'];
        yield 'count service of the wrong type' => [self::posts(['count' => 'app.not_a_strategy']), 'must implement Sidus\FilterBundle\Provider\CountStrategyInterface'];
        yield 'pagination' => [self::posts(['pagination' => ['per_page' => 50, 'max_per_page' => 10]]), 'pagination requires 1 <= per_page (50) <= max_per_page (10)'];
        yield 'provider registered twice' => [
            self::posts(),
            'Provider "bare" is registered twice',
            static function (ContainerConfigurator $c): void {
                $services = $c->services()->defaults()->autoconfigure();
                $services->set(PostsSource::class);
                $services->set(BareFactory::class);
                $services->set(SecondBareFactory::class);
            },
        ];
    }
}
