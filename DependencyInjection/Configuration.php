<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sidus_filter');
        $rootNode
            ->children()
                ->arrayNode('configurations')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('entity')->isRequired(true)->end()
                            ->arrayNode('sortable')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('fields')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('type')->defaultValue('text')->end()
                                        ->scalarNode('label')->defaultNull()->end()
                                        ->arrayNode('attributes')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->variableNode('options')->defaultNull()->end()
                                        ->scalarNode('form_type')->defaultValue('text')->end()
                                        ->variableNode('form_options')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
