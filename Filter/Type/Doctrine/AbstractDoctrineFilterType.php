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

/**
 * Generic filter type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractDoctrineFilterType extends AbstractFilterType
{
    /**
     * @param FilterInterface $filter
     * @param string          $alias
     *
     * @return array
     */
    public function getFullAttributeReferences(FilterInterface $filter, string $alias): array
    {
        $references = [];
        foreach ($filter->getAttributes() as $attribute) {
            if (false === strpos($attribute, '.')) {
                $references[] = $alias.'.'.$attribute;
            } else {
                $references[] = $attribute;
            }
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
