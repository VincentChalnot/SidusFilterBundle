<?php

declare(strict_types=1);

use Sidus\FilterBundle\CacheWarmer\DefaultsWarmer;
use Sidus\FilterBundle\Command\DebugCommand;
use Sidus\FilterBundle\Definition\DefaultsValidator;
use Sidus\FilterBundle\Filter\Type\ChoiceFilterType;
use Sidus\FilterBundle\Filter\Type\DateRangeFilterType;
use Sidus\FilterBundle\Filter\Type\ExactFilterType;
use Sidus\FilterBundle\Filter\Type\NativeFilterType;
use Sidus\FilterBundle\Filter\Type\NotEmptyFilterType;
use Sidus\FilterBundle\Filter\Type\NotNullFilterType;
use Sidus\FilterBundle\Filter\Type\NullFilterType;
use Sidus\FilterBundle\Filter\Type\NumberFilterType;
use Sidus\FilterBundle\Filter\Type\RegexpFilterType;
use Sidus\FilterBundle\Filter\Type\SortFilterType;
use Sidus\FilterBundle\Filter\Type\TextFilterType;
use Sidus\FilterBundle\InMemory\Compiler\AllOfCompiler;
use Sidus\FilterBundle\InMemory\Compiler\AnyOfCompiler;
use Sidus\FilterBundle\InMemory\Compiler\ContainsCompiler;
use Sidus\FilterBundle\InMemory\Compiler\EndsWithCompiler;
use Sidus\FilterBundle\InMemory\Compiler\EqualsCompiler;
use Sidus\FilterBundle\InMemory\Compiler\InCompiler;
use Sidus\FilterBundle\InMemory\Compiler\IsEmptyCompiler;
use Sidus\FilterBundle\InMemory\Compiler\IsNullCompiler;
use Sidus\FilterBundle\InMemory\Compiler\MatchesCompiler;
use Sidus\FilterBundle\InMemory\Compiler\NativeCompiler;
use Sidus\FilterBundle\InMemory\Compiler\NotCompiler;
use Sidus\FilterBundle\InMemory\Compiler\RangeCompiler;
use Sidus\FilterBundle\InMemory\Compiler\SortCompiler;
use Sidus\FilterBundle\InMemory\Compiler\StartsWithCompiler;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autoconfigure();

    foreach ([
        TextFilterType::class, ExactFilterType::class, ChoiceFilterType::class, NumberFilterType::class,
        DateRangeFilterType::class, NullFilterType::class, NotNullFilterType::class, NotEmptyFilterType::class,
        RegexpFilterType::class, SortFilterType::class,
    ] as $type) {
        $services->set($type);
    }
    $services->set(NativeFilterType::class)->args([tagged_locator('sidus_filter.native_builder')]);

    foreach ([
        EqualsCompiler::class, InCompiler::class, ContainsCompiler::class, StartsWithCompiler::class, EndsWithCompiler::class,
        RangeCompiler::class, IsNullCompiler::class, IsEmptyCompiler::class, MatchesCompiler::class, NotCompiler::class,
        AnyOfCompiler::class, AllOfCompiler::class, NativeCompiler::class, SortCompiler::class,
    ] as $compiler) {
        $services->set($compiler);
    }
    $services->set(InMemoryQueryHandlerFactory::class)->args([
        tagged_locator('sidus_filter.in_memory_source'),
        tagged_iterator('sidus_filter.in_memory_compiler'),
    ]);

    $services->set(FilterTypeRegistry::class)->args([tagged_iterator('sidus_filter.filter_type')]);
    // The raw configuration is replaced by the resolved definitions in ListDefinitionPass.
    $services->set(ListDefinitionRegistry::class)->public()->arg('$definitions', []);
    $services->set(QueryHandlerRegistry::class)->public()->args([
        service(ListDefinitionRegistry::class),
        tagged_iterator('sidus_filter.provider_factory'),
        service(FilterTypeRegistry::class),
        '$countStrategies' => null,
    ]);

    $services->set(DefaultsValidator::class)->args([service(FilterTypeRegistry::class)]);
    $services->set(DefaultsWarmer::class)->public()->args([service(ListDefinitionRegistry::class), service(DefaultsValidator::class)])->tag('kernel.cache_warmer');

    if (class_exists(Command::class)) {
        $services->set(DebugCommand::class)->args([service(ListDefinitionRegistry::class), service(FilterTypeRegistry::class)]);
    }
};
