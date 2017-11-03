<?php

namespace Sidus\FilterBundle\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

/**
 * Loads all YML files inside a folder
 */
class ServiceLoader
{
    /** @var string */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function loadFiles(ContainerBuilder $container)
    {
        $finder = new Finder();
        $finder->in($this->path)->name('*.yml')->files();
        $loader = new YamlFileLoader($container, new FileLocator($this->path));
        foreach ($finder as $file) {
            $loader->load($file->getFilename());
        }
    }
}
