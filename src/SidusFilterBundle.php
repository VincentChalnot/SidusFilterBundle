<?php

declare(strict_types=1);

namespace Sidus\FilterBundle;

use Sidus\FilterBundle\DependencyInjection\ListDefinitionPass;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Filter\NativeCriterionBuilderInterface;
use Sidus\FilterBundle\InMemory\InMemoryCriterionCompilerInterface;
use Sidus\FilterBundle\InMemory\InMemorySourceInterface;
use Sidus\FilterBundle\Provider\CriterionCompilerInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SidusFilterBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('lists')
                    ->info('The lists by code: a provider, its options, pagination, count strategy and filters')
                    ->useAttributeAsKey('code')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('provider')->isRequired()->cannotBeEmpty()->info('A parameter is fine, an env var is not: the provider must be known at compile time')->end()
                            ->variableNode('options')->defaultValue([])->info('Validated by the provider')
                                ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The options must be a map.')->end()
                            ->end()
                            ->arrayNode('pagination')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('per_page')->defaultValue(15)->min(1)->end()
                                    ->integerNode('max_per_page')->defaultValue(100)->min(1)->end()
                                ->end()
                            ->end()
                            ->scalarNode('count')->defaultValue('auto')->cannotBeEmpty()->info('auto, none, or the id of a CountStrategyInterface service')->end()
                            ->arrayNode('filters')
                                ->useAttributeAsKey('key')
                                ->normalizeKeys(false)
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('name')->defaultNull()->info('Public input name, defaults to the key')->end()
                                        ->arrayNode('attributes')->info('Dotted paths, or {path, json, cast} maps; defaults to the key')
                                            ->variablePrototype()->end()
                                        ->end()
                                        ->arrayNode('providers')->info('Active only for these providers, ignored entirely otherwise')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->variableNode('options')->defaultValue([])->info('Validated by the filter type')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The options must be a map.')->end()
                                        ->end()
                                        ->variableNode('provider_options')->defaultValue([])->info('Per provider name, validated by that provider')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The provider options must be a map.')->end()
                                        ->end()
                                        ->booleanNode('locked')->defaultFalse()->info('The value is the default, never read from the input')->end()
                                        ->variableNode('default')->defaultNull()->end()
                                        ->variableNode('form')->defaultValue([])->info('Read by the form adapter only')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The form options must be a map.')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $container->parameters()->set(ListDefinitionPass::RAW_LISTS_PARAMETER, $config['lists']);

        $builder->registerForAutoconfiguration(FilterTypeInterface::class)->addTag(ListDefinitionPass::FILTER_TYPE_TAG);
        $builder->registerForAutoconfiguration(QueryHandlerFactoryInterface::class)->addTag(ListDefinitionPass::PROVIDER_FACTORY_TAG);
        $builder->registerForAutoconfiguration(CriterionCompilerInterface::class)->addTag(ListDefinitionPass::CRITERION_COMPILER_TAG);
        $builder->registerForAutoconfiguration(InMemoryCriterionCompilerInterface::class)->addTag('sidus_filter.in_memory_compiler');
        $builder->registerForAutoconfiguration(InMemorySourceInterface::class)->addTag('sidus_filter.in_memory_source');
        $builder->registerForAutoconfiguration(NativeCriterionBuilderInterface::class)->addTag('sidus_filter.native_builder');
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ListDefinitionPass());
    }
}
