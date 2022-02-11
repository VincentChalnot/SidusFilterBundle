<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Better adapter for Doctrine pager count
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DoctrineORMAdapter implements AdapterInterface, ManualCountAdapterInterface
{
    private DoctrineORMPaginator $paginator;

    protected int $nbResults;

    /**
     * @param Query|QueryBuilder $query                                           A Doctrine ORM query or query
     *                                                                            builder.
     * @param Boolean            $fetchJoinCollection                             Whether the query joins a collection
     *                                                                            (true by default).
     * @param Boolean|null       $useOutputWalkers                                Whether to use output walkers
     *                                                                            pagination mode
     */
    public function __construct($query, bool $fetchJoinCollection = true, bool $useOutputWalkers = null)
    {
        $this->paginator = new DoctrineORMPaginator($query, $fetchJoinCollection);
        $this->paginator->setUseOutputWalkers($useOutputWalkers);
    }

    /**
     * Returns the query
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->paginator->getQuery();
    }

    /**
     * Returns whether the query joins a collection.
     *
     * @return Boolean Whether the query joins a collection.
     */
    public function getFetchJoinCollection(): bool
    {
        return $this->paginator->getFetchJoinCollection();
    }

    public function getNbResults(): int
    {
        return $this->nbResults ?? count($this->paginator);
    }

    public function setNbResults(int $nbResult): void
    {
        $this->nbResults = $nbResult;
    }

    public function getSlice(int $offset, int $length): iterable
    {
        $this->paginator
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->paginator->getIterator();
    }
}
