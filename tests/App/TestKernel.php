<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Sidus\FilterBundle\SidusFilterBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new SidusFilterBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/sidus-filter-bundle-tests/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/sidus-filter-bundle-tests/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'form' => ['csrf_protection' => false],
            'http_method_override' => false,
            'router' => ['utf8' => true],
        ]);
        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
            'orm' => [
                'mappings' => [
                    'App' => [
                        'type' => 'attribute',
                        'dir' => __DIR__.'/Entity',
                        'prefix' => 'Sidus\\FilterBundle\\Tests\\App\\Entity',
                    ],
                ],
            ],
        ]);
        $container->extension('sidus_filter', [
            'configurations' => [
                'posts' => [
                    'provider' => 'doctrine',
                    'options' => ['entity' => Entity\Post::class],
                    'results_per_page' => 2,
                    'sortable' => ['title'],
                    'default_sort' => ['title' => 'asc'],
                    'filters' => ['title' => ['type' => 'text']],
                ],
            ],
        ]);
        $container->services()
            ->set(PostListController::class)
            ->autowire()
            ->public()
            ->tag('controller.service_arguments');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('posts', '/posts')->controller(PostListController::class);
    }
}
