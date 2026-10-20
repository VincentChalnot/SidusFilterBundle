<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\App;

use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lists posts the way the DataGrid bundle drives a query handler and returns what was queried.
 */
final readonly class PostListController
{
    public function __construct(
        private QueryHandlerRegistry $registry,
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $handler = $this->registry->getQueryHandler('posts');
        $handler->buildForm($this->formFactory->createNamedBuilder('form', FormType::class, null, ['method' => 'GET']));
        $handler->handleRequest($request);
        $pager = $handler->getPager();

        return new JsonResponse([
            'titles' => array_map(static fn ($post) => $post->title, iterator_to_array($pager->getCurrentPageResults(), false)),
            'page' => $pager->getCurrentPage(),
            'total' => $pager->getNbResults(),
        ]);
    }
}
