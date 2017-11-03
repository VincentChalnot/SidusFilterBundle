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
     * @throws \Exception
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Automatically declare a service for each attribute configured
        /** @var array $configurations */
        $configurations = $config['configurations'];
        foreach ($configurations as $code => $configuration) {
            $this->addConfigurationServiceDefinition($code, $configuration, $container);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('configuration.yml');
        $loader->load('filter_types.yml');
        $loader->load('forms.yml');
    }

    /**
     * @param string           $code
     * @param array            $configuration
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     */
    protected function addConfigurationServiceDefinition($code, array $configuration, ContainerBuilder $container)
    {
        $definition = new Definition(
            new Parameter('sidus_filter.configuration.class'),
            [
                new Reference('sidus_filter.filter.factory'),
                $code,
                $configuration,
                new Reference('doctrine'),
            ]
        );
        $definition->setPublic(false);
        $container->setDefinition('sidus_filter.configuration.'.$code, $definition);
    }
}
