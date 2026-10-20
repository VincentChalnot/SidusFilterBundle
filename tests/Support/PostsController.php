<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Http\ListResponseFactory;
use Sidus\FilterBundle\Http\MapListInput;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class PostsController
{
    public function __construct(private QueryHandlerRegistry $registry, private ListResponseFactory $responses)
    {
    }

    public function __invoke(#[MapListInput('posts')] ListInput $input): JsonResponse
    {
        $result = $this->registry->createQueryHandler('posts')->handle($input);

        return $this->responses->create($result, static fn (array $post): array => ['id' => $post['id'], 'title' => $post['title']]);
    }
}
