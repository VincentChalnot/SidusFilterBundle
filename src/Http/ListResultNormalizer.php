<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Http;

use Sidus\FilterBundle\Result\ListResult;

/**
 * A result to the API shape `{items, meta: {page, per_page, total, pages, next}}`. `total` and `pages` are null
 * with `count: none`; `next` is the next page number or null. Items are passed through the optional callable, e.g.
 * `fn ($item) => $serializer->normalize($item, null, ['groups' => ['list']])`.
 */
final readonly class ListResultNormalizer
{
    private ?\Closure $itemNormalizer;

    /**
     * @param (callable(mixed): mixed)|null $itemNormalizer
     */
    public function __construct(?callable $itemNormalizer = null)
    {
        $this->itemNormalizer = null === $itemNormalizer ? null : $itemNormalizer(...);
    }

    /**
     * @param (callable(mixed): mixed)|null $itemNormalizer overrides the constructor's one for this call
     *
     * @return array{items: list<mixed>, meta: array{page: int, per_page: int, total: int|null, pages: int|null, next: int|null}}
     */
    public function normalize(ListResult $result, ?callable $itemNormalizer = null): array
    {
        $normalizer = null === $itemNormalizer ? $this->itemNormalizer : $itemNormalizer(...);

        return [
            'items' => null === $normalizer ? $result->items() : array_map($normalizer, $result->items()),
            'meta' => [
                'page' => $result->page(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'pages' => $result->pages(),
                'next' => $result->hasNextPage() ? $result->page() + 1 : null,
            ],
        ];
    }
}
