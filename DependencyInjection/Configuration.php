<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link
 * http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
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
     * @throws \InvalidArgumentException
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
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function appendFilterDefinition(NodeBuilder $filterDefinition)
    {
        $fieldDefinition = $filterDefinition
            ->scalarNode('provider')->isRequired()->end()
            ->integerNode('results_per_page')->defaultValue(15)->end()
            ->arrayNode('sortable')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('default_sort')
                ->prototype('scalar')->defaultValue([])->end()
            ->end()
            ->variableNode('options')
                ->defaultValue([])
                ->validate()
                    ->ifTrue(function ($value) {
                        return !\is_array($value) && null !== $value;
                    })
                    ->thenInvalid('"options" configuration must be an array or left empty')
                ->end()
            ->end()
            ->arrayNode('filters')
                ->prototype('array')
                    ->children();

        $this->appendFieldDefinition($fieldDefinition);

        $fieldDefinition->end()
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $fieldDefinition
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function appendFieldDefinition(NodeBuilder $fieldDefinition)
    {
        $fieldDefinition
            ->scalarNode('type')->defaultValue('text')->end()
            ->scalarNode('label')->defaultNull()->end()
            ->scalarNode('form_type')->defaultNull()->end()
            ->arrayNode('attributes')
                ->prototype('scalar')->defaultNull()->end()
            ->end()
            ->variableNode('options')
                ->defaultValue([])
                ->validate()
                ->ifTrue(function ($value) {
                    return !\is_array($value) && null !== $value;
                })
                ->thenInvalid('"options" configuration must be an array or left empty')
                ->end()
            ->end()
            ->variableNode('form_options')
                ->defaultValue([])
                ->validate()
                ->ifTrue(function ($value) {
                    return !\is_array($value) && null !== $value;
                })
                ->thenInvalid('"form_options" configuration must be an array or left empty')
                ->end()
            ->end();
    }
}
