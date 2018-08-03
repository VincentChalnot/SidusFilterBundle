<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Filter\Type\AbstractFilterType;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;

/**
 * Generic filter type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractDoctrineFilterType extends AbstractFilterType
{
    /**
     * Returns an array of DQL references ready for filtering, handling nested entities through joins
     *
     * @param FilterInterface               $filter
     * @param DoctrineQueryHandlerInterface $queryHandler
     *
     * @return array
     */
    public function getFullAttributeReferences(
        FilterInterface $filter,
        DoctrineQueryHandlerInterface $queryHandler
    ): array {
        $references = [];
        foreach ($filter->getAttributes() as $attributePath) {
            $attributesList = explode('.', $attributePath);
            $previousAttribute = $queryHandler->getAlias().'.'.array_shift($attributesList);
            $resolvedAttribute = $previousAttribute;

            // Remaining attributes in attributeList are nested so we need joins
            foreach ($attributesList as $nestedAttribute) {
                $qb = $queryHandler->getQueryBuilder();
                $joinAlias = uniqid('nested');
                $qb->join($previousAttribute, $joinAlias);
                $resolvedAttribute = $joinAlias.'.'.$nestedAttribute;
            }
            $references[] = $resolvedAttribute;
        }

        return $references;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'doctrine';
    }
}
