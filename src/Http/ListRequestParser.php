<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Http;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Request to raw {@see ListInput}, from the query string (`?filters[title]=x&sort=-date&page=2&per_page=50`) or from
 * the same structure as a JSON body (body values win over the query). Nothing is validated here: invalid parts
 * become violations in the result.
 *
 * With an empty `$filtersKey` the filters are the top-level parameters (`?title=x&sort=-date`), minus the three
 * reserved keys. A top-level `$sortKey` value feeds the list's sort filter (whatever its public name).
 */
final readonly class ListRequestParser
{
    public function __construct(
        private string $filtersKey = 'filters',
        private string $sortKey = 'sort',
        private string $pageKey = 'page',
        private string $perPageKey = 'per_page',
    ) {
    }

    public function parse(Request $request, ?ListDefinition $definition = null): ListInput
    {
        $data = $request->query->all();
        if ('json' === $request->getContentTypeFormat() && '' !== ($content = $request->getContent())) {
            try {
                $body = json_decode($content, true, 32, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new BadRequestHttpException('The request body is not valid JSON.', $e);
            }
            if (!\is_array($body)) {
                throw new BadRequestHttpException('The JSON body must be an object.');
            }
            $data = [...$data, ...$body];
        }

        $reserved = [$this->sortKey, $this->pageKey, $this->perPageKey];
        if ('' === $this->filtersKey) {
            $filters = array_diff_key($data, array_flip($reserved));
        } else {
            $filters = \is_array($data[$this->filtersKey] ?? null) ? $data[$this->filtersKey] : [];
        }
        $sortFilter = $definition?->findSortFilter()?->publicName() ?? 'sort';
        if (\array_key_exists($this->sortKey, $data) && !\array_key_exists($sortFilter, $filters)) {
            $filters[$sortFilter] = $data[$this->sortKey];
        }

        return new ListInput($filters, $data[$this->pageKey] ?? null, $data[$this->perPageKey] ?? null);
    }
}
