<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Sidus\FilterBundle\DependencyInjection\Loader\ServiceLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusFilterExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $registry = $container->getDefinition('sidus_filter.registry.query_handler');
        /** @var array $configurations */
        $configurations = $config['configurations'];
        foreach ($configurations as $code => $configuration) {
            $registry->addMethodCall('addRawQueryHandlerConfiguration', [$code, $configuration]);
        }

        $loader = new ServiceLoader(__DIR__.'/../Resources/config/services');
        $loader->loadFiles($container);
    }
}
