<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Filter logic for choices using available values in Doctrine entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ChoiceFilterType extends CustomChoiceFilterType
{
    /**
     * Trying to automatically resolve choice options from database
     *
     * {@inheritdoc}
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }

        if (isset($this->formOptions['choices']) || isset($filter->getFormOptions()['choices'])) {
            return parent::getFormOptions($queryHandler, $filter);
        }

        return array_merge(
            $this->formOptions,
            $filter->getFormOptions(),
            ['choices' => $this->getChoices($queryHandler, $filter)]
        );
    }

    protected function getChoices(DoctrineQueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        $choices = [];
        $originalQb = clone $queryHandler->getQueryBuilder(); // Saving current query builder state

        foreach ($filter->getAttributes() as $attributePath) {
            $metadata = $queryHandler->getAttributeMetadata($attributePath);

            if (isset($metadata['targetEntity'])) {
                $m = "Attribute path {$attributePath} resolve to a relational attribute, use the 'entity' filter ";
                $m .= "type instead of the 'choice' type";
                throw new \LogicException($m);
            }

            $column = $queryHandler->resolveAttributeAlias($attributePath);

            $qb = clone $queryHandler->getQueryBuilder();
            $qb->select("{$column} AS __value")
                ->groupBy($column);

            foreach ($qb->getQuery()->getArrayResult() as $result) {
                $value = $result['__value'];
                $choices[$value] = $value;
            }
        }

        // Rolling back to previous query builder to revert automatic joints
        $queryHandler->setQueryBuilder($originalQb, $queryHandler->getAlias());

        return $choices;
    }
}
