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

namespace Sidus\FilterBundle\Doctrine\Filter\Type;

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

    public static function getProvider(): string
    {
        return 'doctrine';
    }
}
