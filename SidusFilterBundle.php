<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle;

use Sidus\BaseBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Injecting registry compiler passes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
                FilterTypeRegistry::class,
                'sidus.filter_type',
                'addFilterType'
            )
        );
        $container->addCompilerPass(
            new GenericCompilerPass(
                QueryHandlerRegistry::class,
                'sidus.query_handler_factory',
                'addQueryHandlerFactory'
            )
        );
    }
}
