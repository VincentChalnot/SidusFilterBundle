<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\Result\ListResult;

/**
 * Exposes an already computed {@see ListResult} to Pagerfanta (templates and views keep `pager`-based pagination):
 * the slice is the result's page, the number of results its (lazy) total. With `count: none` the total is unknown:
 * the adapter reports `(page - 1) * per_page + items` plus one when a next page exists, which is enough for
 * prev/next navigation.
 *
 * @implements AdapterInterface<mixed>
 */
final readonly class ListResultAdapter implements AdapterInterface
{
    public function __construct(private ListResult $result)
    {
    }

    /** A pager on the result's page and page size; a page beyond the end is allowed (empty) like in the result. */
    public static function createPager(ListResult $result): Pagerfanta
    {
        $pager = new Pagerfanta(new self($result));
        $pager->setAllowOutOfRangePages(true);
        $pager->setMaxPerPage($result->perPage());
        $pager->setCurrentPage($result->page());

        return $pager;
    }

    public function getNbResults(): int
    {
        $total = $this->result->total();
        if (null !== $total) {
            return $total;
        }

        return ($this->result->page() - 1) * $this->result->perPage() + \count($this->result->items()) + ($this->result->hasNextPage() ? 1 : 0);
    }

    public function getSlice(int $offset, int $length): iterable
    {
        if ($offset !== ($this->result->page() - 1) * $this->result->perPage()) {
            throw new \LogicException('A ListResult only holds its own page: build a new result to read another page.');
        }

        return \array_slice($this->result->items(), 0, $length);
    }
}
