<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;
use function count;

/**
 * Better adapter for Doctrine pager count
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DoctrineORMAdapter implements AdapterInterface, ManualCountAdapterInterface
{
    /** @var DoctrineORMPaginator */
    private $paginator;

    /** @var int */
    protected $nbResults;

    /**
     * @param Query|QueryBuilder $query                                           A Doctrine ORM query or query
     *                                                                            builder.
     * @param Boolean            $fetchJoinCollection                             Whether the query joins a collection
     *                                                                            (true by default).
     * @param Boolean|null       $useOutputWalkers                                Whether to use output walkers
     *                                                                            pagination mode
     */
    public function __construct($query, $fetchJoinCollection = true, $useOutputWalkers = null)
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
    public function getFetchJoinCollection()
    {
        return $this->paginator->getFetchJoinCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        if (null !== $this->nbResults) {
            return $this->nbResults;
        }

        return count($this->paginator);
    }

    /**
     * @param int $nbResults
     */
    public function setNbResults(int $nbResults): void
    {
        $this->nbResults = $nbResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $this->paginator
            ->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->paginator->getIterator();
    }
}
