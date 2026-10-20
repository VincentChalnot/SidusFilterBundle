<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Sidus\FilterBundle\Tests\Support\PrefixedFilterType;
use Sidus\FilterBundle\Tests\Support\Prefixer;
use Sidus\FilterBundle\Tests\Support\RecordingMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class MapperReceivesTheListTest extends TestCase
{
    public function testAMapperGetsTheListDefinitionOfTheFilter(): void
    {
        $kernel = TestKernel::create(
            ['posts' => ['provider' => 'in_memory', 'options' => ['source' => PostsSource::class], 'filters' => ['title' => ['type' => 'prefixed']]]],
            static function (ContainerConfigurator $c): void {
                $services = $c->services()->defaults()->autowire()->autoconfigure();
                $services->set(PostsSource::class);
                $services->set(Prefixer::class);
                $services->set(PrefixedFilterType::class);
                $services->set(RecordingMapper::class);
            },
        );
        $kernel->boot();
        $container = $kernel->getContainer();

        $form = $container->get(ListFormFactory::class)->create($container->get(QueryHandlerRegistry::class)->getDefinition('posts'));
        $view = $form->createView();

        self::assertSame('posts', $view['filters']['title']->vars['attr']['data-list']);
        self::assertSame(PostsSource::class, $view['filters']['title']->vars['attr']['data-source']);
        $kernel->shutdown();
    }
}
