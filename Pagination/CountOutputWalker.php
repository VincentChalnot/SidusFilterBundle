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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use RuntimeException;
use function count;

/**
 * Custom CountOutputWalker copied from Doctrine with huge performance improvements and fix for Oracle
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CountOutputWalker extends SqlWalker
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * Stores various parameters that are otherwise unavailable
     * because Doctrine\ORM\Query\SqlWalker keeps everything private without
     * accessors.
     *
     * @param Query              $query
     * @param ParserResult $parserResult
     * @param array                            $queryComponents
     *
     * @throws DBALException
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();

        parent::__construct($query, $parserResult, $queryComponents);
    }

    /**
     * Walks down a SelectStatement AST node, wrapping it in a COUNT (SELECT DISTINCT).
     *
     * Note that the ORDER BY clause is not removed. Many SQL implementations (e.g. MySQL)
     * are able to cache subqueries. By keeping the ORDER BY clause intact, the limitSubQuery
     * that will most likely be executed next can be read from the native SQL cache.
     *
     * @param SelectStatement $AST
     *
     * @throws RuntimeException
     * @throws MappingException
     * @throws OptimisticLockException
     * @throws QueryException
     *
     * @return string
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        if ('mssql' === $this->platform->getName()) {
            $AST->orderByClause = null;
        }

        if ($AST->groupByClause) {
            $countExpr = $this->platform->getCountExpression('*');
            $sql = parent::walkSelectStatement($AST);

            return "SELECT {$countExpr} AS dctrn_count FROM ({$sql}) dctrn_table";
        }

        $this->getQuery()->setHint(self::HINT_DISTINCT, true);

        if ($AST->havingClause) {
            throw new RuntimeException(
                'Cannot count query that uses a HAVING clause. Use the output walkers for pagination'
            );
        }

        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new RuntimeException(
                'Cannot count query which selects two FROM components, cannot make distinction'
            );
        }

        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        /** @var ClassMetadata $rootClass */
        $rootClass = $this->getQueryComponent($rootAlias)['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $identifierFieldName
        );
        $pathExpression->type = $pathType;

        $distinct = $this->getQuery()->getHint(self::HINT_DISTINCT);
        $AST->selectClause->selectExpressions = [
            new SelectExpression(
                new AggregateExpression('COUNT', $pathExpression, $distinct),
                null
            ),
        ];

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $AST->orderByClause = null;

        $sql = parent::walkSelectStatement($AST);

        return str_replace(
            "AS {$this->platform->getSQLResultCasing('sclr_0')} FROM",
            "AS {$this->platform->getSQLResultCasing('dctrn_count')} FROM",
            $sql
        );
    }
}
