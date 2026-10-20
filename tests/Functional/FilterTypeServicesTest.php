<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\CacheWarmer\DefaultsWarmer;
use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Filter\Type\BuiltInFilterTypes;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Sidus\FilterBundle\Tests\Support\PrefixedFilterType;
use Sidus\FilterBundle\Tests\Support\Prefixer;
use Sidus\FilterBundle\Tests\Support\TwinPrefixedFilterType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Filter types may have constructor dependencies: only their static half is used at compile time.
 */
final class FilterTypeServicesTest extends TestCase
{
    private static function services(bool $twin = false): \Closure
    {
        return static function (ContainerConfigurator $c) use ($twin): void {
            $services = $c->services()->defaults()->autowire()->autoconfigure();
            $services->set(PostsSource::class);
            $services->set(Prefixer::class);
            $services->set(PrefixedFilterType::class);
            if ($twin) {
                $services->set(TwinPrefixedFilterType::class);
            }
        };
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return array<string, array<string, mixed>>
     */
    private static function lists(array $filter = []): array
    {
        return ['posts' => [
            'provider' => 'in_memory',
            'options' => ['source' => PostsSource::class],
            'filters' => ['title' => ['type' => 'prefixed', 'attributes' => ['title']] + $filter],
        ]];
    }

    public function testATypeWithConstructorDependenciesCompilesAndUsesItsServicesPerRequest(): void
    {
        $kernel = TestKernel::create(self::lists(), self::services());
        $kernel->boot();

        $result = $kernel->getContainer()->get(QueryHandlerRegistry::class)->createQueryHandler('posts')->handle(ListInput::fromArray(['filters' => ['title' => 'hello']]));

        self::assertSame([2], array_column($result->items(), 'id'), '"hello" went through the injected Prefixer');
        $kernel->shutdown();
    }

    public function testDefaultsAreValidatedAtWarmUpWithTheRealTypes(): void
    {
        $valid = TestKernel::create(self::lists(['default' => 'hello']), self::services());
        $valid->boot();
        self::assertSame([], $valid->getContainer()->get(DefaultsWarmer::class)->warmUp(sys_get_temp_dir()));
        $valid->shutdown();

        // Building the cache runs the warmers: a bad default stops the kernel exactly like `cache:warmup` would.
        $invalid = TestKernel::create(self::lists(['default' => 'forbidden']), self::services());
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('List "posts", filter "title" (type "prefixed"): the default value is invalid: Rejected by the prefixer.');

        $invalid->boot();
    }

    public function testTheWarmerIsRegisteredAsANonOptionalCacheWarmer(): void
    {
        $kernel = TestKernel::create(self::lists(), self::services());
        $kernel->boot();

        self::assertFalse($kernel->getContainer()->get(DefaultsWarmer::class)->isOptional());
        $kernel->shutdown();
    }

    public function testTwoServicesWithTheSameTypeNameFailAtCompileTime(): void
    {
        $kernel = TestKernel::create(self::lists(), self::services(twin: true));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Filter type "prefixed" is registered twice');

        $kernel->boot();
    }

    public function testTheCompileTimeHalfOfEveryShippedTypeIsStatic(): void
    {
        foreach (BuiltInFilterTypes::classes() as $name => $class) {
            self::assertSame($name, $class::getName());
            foreach (['getName', 'configureOptions', 'getRequiredOperations'] as $method) {
                self::assertTrue(new \ReflectionMethod($class, $method)->isStatic(), $class.'::'.$method.' must be static');
            }
        }
        foreach (['normalize', 'buildCriterion'] as $method) {
            self::assertFalse(new \ReflectionMethod(FilterTypeInterface::class, $method)->isStatic());
        }
    }
}
