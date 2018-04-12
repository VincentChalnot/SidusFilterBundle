<?php

namespace Sidus\FilterBundle\Pagination;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class CountOutputWalker
 *
 * @package Sidus\FilterBundle\Pagination
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CountOutputWalker extends SqlWalker
{
    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * @var \Doctrine\ORM\Query\ResultSetMapping
     */
    private $rsm;

    /**
     * @var array
     */
    private $queryComponents;

    /**
     * Constructor.
     *
     * Stores various parameters that are otherwise unavailable
     * because Doctrine\ORM\Query\SqlWalker keeps everything private without
     * accessors.
     *
     * @param \Doctrine\ORM\Query              $query
     * @param \Doctrine\ORM\Query\ParserResult $parserResult
     * @param array                            $queryComponents
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();
        $this->rsm = $parserResult->getResultSetMapping();
        $this->queryComponents = $queryComponents;

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
     * @return string
     *
     * @throws \RuntimeException
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        if ($this->platform->getName() === "mssql") {
            $AST->orderByClause = null;
        }

        if ($AST->groupByClause) {
            $sql = parent::walkSelectStatement($AST);

            return sprintf(
                'SELECT %s AS dctrn_count FROM (%s) dctrn_table',
                $this->platform->getCountExpression('*'),
                $sql
            );
        }

        $this->getQuery()->setHint(self::HINT_DISTINCT, true);

        if ($AST->havingClause) {
            throw new \RuntimeException(
                'Cannot count query that uses a HAVING clause. Use the output walkers for pagination'
            );
        }

        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;

        if (count($from) > 1) {
            throw new \RuntimeException(
                "Cannot count query which selects two FROM components, cannot make distinction"
            );
        }

        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $this->getQueryComponent($rootAlias)['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $rootAlias,
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

        return str_replace('AS sclr_0 FROM', 'AS dctrn_count FROM', $sql);
    }
}
