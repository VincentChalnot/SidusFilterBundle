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

use ArrayIterator;
use Countable;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;
use IteratorAggregate;

/**
 * Better paginator with simpler count query
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DoctrineORMPaginator implements Countable, IteratorAggregate
{
    use SQLResultCasing;

    protected Query $query;

    protected bool $fetchJoinCollection;

    protected ?bool $useOutputWalkers = null;

    protected ?int $count = null;

    /**
     * @param Query|QueryBuilder $query               A Doctrine ORM query or query builder.
     * @param bool               $fetchJoinCollection Whether the query joins a collection (true by default).
     */
    public function __construct(Query|QueryBuilder $query, bool $fetchJoinCollection = true)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $this->query = $query;
        $this->fetchJoinCollection = $fetchJoinCollection;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Returns whether the query joins a collection.
     */
    public function getFetchJoinCollection(): bool
    {
        return $this->fetchJoinCollection;
    }

    /**
     * Returns whether the paginator will use an output walker.
     */
    public function getUseOutputWalkers(): ?bool
    {
        return $this->useOutputWalkers;
    }

    /**
     * Sets whether the paginator will use an output walker.
     */
    public function setUseOutputWalkers(?bool $useOutputWalkers): self
    {
        $this->useOutputWalkers = $useOutputWalkers;

        return $this;
    }

    public function count(): int
    {
        if (null === $this->count) {
            try {
                $this->count = array_sum(array_map('current', $this->getCountQuery()->getScalarResult()));
            } catch (NoResultException) {
                $this->count = 0;
            }
        }

        return $this->count;
    }

    public function getIterator(): \Traversable
    {
        $offset = $this->query->getFirstResult();
        $length = $this->query->getMaxResults();

        if ($this->fetchJoinCollection) {
            $subQuery = $this->cloneQuery($this->query);

            if ($this->useOutputWalker($subQuery)) {
                $subQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);
            } else {
                $this->appendTreeWalker($subQuery, LimitSubqueryWalker::class);
            }

            $subQuery->setFirstResult($offset)->setMaxResults($length);

            $ids = array_map('current', $subQuery->getScalarResult());

            $whereInQuery = $this->cloneQuery($this->query);
            // don't do this for an empty id array
            if (0 === count($ids)) {
                return new ArrayIterator([]);
            }

            $this->appendTreeWalker($whereInQuery, WhereInWalker::class);
            $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids));
            $whereInQuery->setFirstResult(null)->setMaxResults(null);
            $whereInQuery->setParameter(WhereInWalker::PAGINATOR_ID_ALIAS, $ids);
            $whereInQuery->setCacheable($this->query->isCacheable());

            $result = $whereInQuery->getResult($this->query->getHydrationMode());
        } else {
            $result = $this->cloneQuery($this->query)
                ->setMaxResults($length)
                ->setFirstResult($offset)
                ->setCacheable($this->query->isCacheable())
                ->getResult($this->query->getHydrationMode());
        }

        return new ArrayIterator($result);
    }

    protected function cloneQuery(Query $query): Query
    {
        $cloneQuery = clone $query;

        $cloneQuery->setParameters(clone $query->getParameters());
        $cloneQuery->setCacheable(false);

        foreach ($query->getHints() as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * Determines whether to use an output walker for the query.
     */
    protected function useOutputWalker(Query $query): bool
    {
        return $this->useOutputWalkers ?? (false === (bool) $query->getHint(Query::HINT_CUSTOM_OUTPUT_WALKER));
    }

    /**
     * Appends a custom tree walker to the tree walkers hint.
     */
    protected function appendTreeWalker(Query $query, string $walkerClass): void
    {
        $hints = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if (false === $hints) {
            $hints = [];
        }

        $hints[] = $walkerClass;
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $hints);
    }

    /**
     * Returns Query prepared to count.
     */
    protected function getCountQuery(): Query
    {
        $countQuery = $this->cloneQuery($this->query);

        if ($this->useOutputWalker($countQuery)) {
            $platform = $countQuery->getEntityManager()->getConnection()->getDatabasePlatform(); // law of demeter win
            if (null === $platform) {
                throw new \UnexpectedValueException('Missing database platform');
            }

            $rsm = new ResultSetMapping();
            $rsm->addScalarResult($this->getSQLResultCasing($platform, 'dctrn_count'), 'count');

            $countQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
            $countQuery->setResultSetMapping($rsm);
        } else {
            $this->appendTreeWalker($countQuery, CountWalker::class);
        }

        $countQuery->setFirstResult(null)->setMaxResults(null);

        $parser = new Parser($countQuery);
        $parameterMappings = $parser->parse()->getParameterMappings();
        /* @var $parameters Collection|Parameter[] */
        $parameters = $countQuery->getParameters();

        foreach ($parameters as $key => $parameter) {
            $parameterName = $parameter->getName();

            if (!(isset($parameterMappings[$parameterName]) || array_key_exists($parameterName, $parameterMappings))) {
                unset($parameters[$key]);
            }
        }

        $countQuery->setParameters($parameters);

        return $countQuery;
    }
}
