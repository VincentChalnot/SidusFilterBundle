<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Doctrine\DoctrineFilterInterface;
use Sidus\FilterBundle\Filter\Type\AbstractFilterType;

/**
 * Generic filter type
 */
abstract class AbstractDoctrineFilterType extends AbstractFilterType implements DoctrineFilterTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDoctrineFormOptions(DoctrineFilterInterface $filter, QueryBuilder $qb, $alias)
    {
        return $this->formOptions;
    }
}
