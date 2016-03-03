<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root = 'sidus_filter')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);

        $filterDefinition = $rootNode
            ->children()
                ->arrayNode('configurations')
                    ->prototype('array')
                        ->children();

        $this->appendFilterDefinition($filterDefinition);


        $filterDefinition->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $filterDefinition
     */
    protected function appendFilterDefinition(NodeBuilder $filterDefinition)
    {
        $fieldDefinition = $filterDefinition
            ->scalarNode('entity')->isRequired()->end()
            ->integerNode('results_per_page')->defaultValue(15)->end()
            ->arrayNode('sortable')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('fields')
                ->prototype('array')
                    ->children();

        $this->appendFieldDefinition($fieldDefinition);

        $fieldDefinition->end()
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $fieldDefinition
     */
    protected function appendFieldDefinition(NodeBuilder $fieldDefinition)
    {
        $fieldDefinition
            ->scalarNode('type')->defaultValue('text')->end()
            ->scalarNode('label')->defaultNull()->end()
            ->arrayNode('attributes')
                ->prototype('scalar')->end()
            ->end()
            ->variableNode('options')->defaultNull()->end()
            ->scalarNode('form_type')->defaultValue('text')->end()
            ->variableNode('form_options')->defaultNull()->end();
    }
}
