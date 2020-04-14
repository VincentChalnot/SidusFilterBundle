<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\DependencyInjection;

use Sidus\BaseBundle\DependencyInjection\Loader\ServiceLoader;
use Sidus\BaseBundle\DependencyInjection\SidusBaseExtension;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loading configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusFilterExtension extends SidusBaseExtension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        parent::load($configs, $container);

        // Only load doctrine configuration if bundle is enabled
        if (array_key_exists('DoctrineBundle', $container->getParameter('kernel.bundles'))) {
            $doctrineLoader = new ServiceLoader($container);
            $doctrineLoader->loadFiles(__DIR__.'/../Resources/config/doctrine');
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $registry = $container->getDefinition(QueryHandlerRegistry::class);
        /** @var array $configurations */
        $configurations = $config['configurations'];
        foreach ($configurations as $code => $configuration) {
            $registry->addMethodCall('addRawQueryHandlerConfiguration', [$code, $configuration]);
        }
    }
}
