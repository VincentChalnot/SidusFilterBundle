<?php

namespace Sidus\FilterBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FilterTypeCompilerPass implements CompilerPassInterface
{
    /**
     * Inject tagged attribute types into configuration handler
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sidus_filter.filter_type_configuration.handler')) {
            return;
        }

        $definition = $container->findDefinition('sidus_filter.filter_type_configuration.handler');
        $taggedServices = $container->findTaggedServiceIds('sidus.filter_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addFilterType',
                [new Reference($id)]
            );
        }
    }
}
