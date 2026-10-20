<?php

declare(strict_types=1);

/*
 * Boots a kernel with the optional packages made invisible to the autoloader (symfony/form, Twig, Pagerfanta) and
 * prints what a request produces as JSON. Run by LayeringTest in a separate process.
 */

use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$composer = require \dirname(__DIR__, 2).'/vendor/autoload.php';
$blocked = ['Symfony\\Component\\Form\\', 'Symfony\\Bridge\\Twig\\', 'Twig\\', 'Pagerfanta\\', 'Sidus\\FilterBundle\\Form\\', 'Sidus\\FilterBundle\\Pagerfanta\\'];
spl_autoload_unregister([$composer, 'loadClass']);
spl_autoload_register(static function (string $class) use ($composer, $blocked): void {
    foreach ($blocked as $prefix) {
        if (str_starts_with($class, $prefix)) {
            return;
        }
    }
    $composer->loadClass($class);
});

$kernel = TestKernel::create(
    ['posts' => [
        'provider' => 'in_memory',
        'options' => ['source' => PostsSource::class],
        'pagination' => ['per_page' => 2, 'max_per_page' => 5],
        'filters' => [
            'title' => ['type' => 'text'],
            'order' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id'], 'default' => [['column' => 'id', 'direction' => 'desc']]]],
        ],
    ]],
    static function (ContainerConfigurator $c): void {
        $c->extension('framework', ['form' => ['enabled' => false]]);
        $c->services()->defaults()->autoconfigure()->set(PostsSource::class);
    },
);
$kernel->boot();
$container = $kernel->getContainer();
$result = $container->get(QueryHandlerRegistry::class)->createQueryHandler('posts')->handle(ListInput::fromArray(['filters' => ['title' => 'hello']]));

echo json_encode([
    'form_available' => interface_exists(Symfony\Component\Form\FormInterface::class),
    'pagerfanta_available' => class_exists(Pagerfanta\Pagerfanta::class),
    'twig_available' => class_exists(Twig\Environment::class),
    'form_factory' => $container->has(Sidus\FilterBundle\Form\ListFormFactory::class),
    'ids' => array_column($result->items(), 'id'),
    'total' => $result->total(),
    'http' => $container->has(Sidus\FilterBundle\Http\ListRequestParser::class),
], \JSON_THROW_ON_ERROR);
