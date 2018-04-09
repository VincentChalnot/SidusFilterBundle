<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Sidus\BaseBundle\DependencyInjection\Loader\ServiceLoader;
use Sidus\BaseBundle\DependencyInjection\SidusBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusFilterExtension extends SidusBaseExtension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        parent::load($configs, $container);

        if (array_key_exists('DoctrineBundle', $container->getParameter('kernel.bundles'))) {
            $doctrineLoader = new ServiceLoader($container);
            $doctrineLoader->loadFiles(__DIR__.'/../Resources/config/doctrine');
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $registry = $container->getDefinition('sidus_filter.registry.query_handler');
        /** @var array $configurations */
        $configurations = $config['configurations'];
        foreach ($configurations as $code => $configuration) {
            $registry->addMethodCall('addRawQueryHandlerConfiguration', [$code, $configuration]);
        }
    }
}
