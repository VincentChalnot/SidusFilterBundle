<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Filtering on dates with Doctrine entities
 */
class DateRangeFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleForm(QueryHandlerInterface $queryHandler, FilterInterface $filter, FormInterface $form)
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }
        $data = $form->getData();
        if (null === $data || !\is_array($data)) {
            return;
        }

        $startDate = $data[DateRangeType::START_NAME] ?? null;
        $endDate = $data[DateRangeType::END_NAME] ?? null;
        if (null === $startDate && null === $endDate) {
            return;
        }

        $qb = $queryHandler->getQueryBuilder();
        $columns = $this->getFullAttributeReferences($filter, $queryHandler->getAlias());
        if ($startDate instanceof \DateTimeInterface) {
            $this->buildQb($columns, $qb, $startDate, '>=');
        }
        if ($endDate instanceof \DateTimeInterface) {
            $this->buildQb($columns, $qb, $endDate, '<=');
        }
    }

    /**
     * @param array        $columns
     * @param QueryBuilder $qb
     * @param \DateTime    $value
     * @param string       $operator
     */
    protected function buildQb(array $columns, QueryBuilder $qb, \DateTime $value, string $operator)
    {
        $dql = [];
        foreach ($columns as $column) {
            $uid = uniqid('date');
            $dql[] = "{$column} {$operator} :{$uid}";
            $qb->setParameter($uid, $value);
        }
        if (0 < \count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
