<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
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
            $references[] = $queryHandler->resolveAttributeAlias($attributePath);
        }

        return $references;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeMetadatas(
        FilterInterface $filter,
        DoctrineQueryHandlerInterface $queryHandler
    ): array {
        $metadata = [];
        foreach ($filter->getAttributes() as $attributePath) {
            $metadata[] = $queryHandler->getAttributeMetadata($attributePath);
        }

        return $metadata;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'doctrine';
    }
}
