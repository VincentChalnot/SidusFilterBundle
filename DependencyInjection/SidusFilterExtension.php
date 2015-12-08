<?php

namespace Sidus\FilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusFilterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Automatically declare a service for each attribute configured
        foreach ($config['configurations'] as $code => $configuration) {
            $this->addConfigurationServiceDefinition($code, $configuration, $container);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form.yml');
        $loader->load('filter_types.yml');
    }

    /**
     * @param string $code
     * @param array $configuration
     * @param ContainerBuilder $container
     */
    protected function addConfigurationServiceDefinition($code, array $configuration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_filter.configuration.class'), [
            $code,
            new Reference('doctrine'),
            new Reference('sidus_filter.filter.factory'),
            $configuration,
        ]);
        $container->setDefinition('sidus_filter.configuration.' . $code, $definition);
    }
}
