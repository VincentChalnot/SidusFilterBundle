<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\App;

use Sidus\FilterBundle\SidusFilterBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Boots the bundle with the configuration given by the test, in an isolated cache directory.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param \Closure(ContainerConfigurator): void|null $configure
     */
    public function __construct(
        private readonly ?\Closure $configure = null,
        private readonly string $id = 'default',
    ) {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SidusFilterBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/sidus-filter-bundle-tests/'.$this->id.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/sidus-filter-bundle-tests/'.$this->id.'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['test' => true, 'secret' => 'test', 'http_method_override' => false]);
        if (null !== $this->configure) {
            ($this->configure)($container);
        }
    }
}
