<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\App;

use Sidus\FilterBundle\SidusFilterBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Boots the bundle with the lists and services given by the test, in an isolated cache directory that is wiped
 * before the first boot (so that every boot compiles the container, unless `$wipeCache` is false).
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
        private readonly bool $wipeCache = true,
    ) {
        parent::__construct('test', true);
    }

    /**
     * @param array<string, array<string, mixed>>        $lists
     * @param \Closure(ContainerConfigurator): void|null $services
     */
    public static function create(array $lists, ?\Closure $services = null, ?string $id = null, bool $wipeCache = true): self
    {
        return new self(static function (ContainerConfigurator $container) use ($lists, $services): void {
            $container->extension('sidus_filter', ['lists' => $lists]);
            if (null !== $services) {
                $services($container);
            }
        }, $id ?? uniqid('k', true), $wipeCache);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SidusFilterBundle();
    }

    public function boot(): void
    {
        if (!$this->booted && $this->wipeCache) {
            new Filesystem()->remove($this->getCacheDir());
        }
        parent::boot();
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
