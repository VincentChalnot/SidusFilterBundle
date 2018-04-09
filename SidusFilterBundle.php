<?php

namespace Sidus\FilterBundle;

use Sidus\BaseBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SidusFilterBundle
 *
 * @package Sidus\FilterBundle
 */
class SidusFilterBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new GenericCompilerPass(
                'sidus_filter.registry.filter_type',
                'sidus.filter_type',
                'addFilterType'
            )
        );
        $container->addCompilerPass(
            new GenericCompilerPass(
                'sidus_filter.registry.query_handler',
                'sidus.query_handler_factory',
                'addQueryHandlerFactory'
            )
        );
    }
}
