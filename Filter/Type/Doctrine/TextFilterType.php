<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Simple text filtering with Doctrine entities
 */
class TextFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleForm(QueryHandlerInterface $queryHandler, FilterInterface $filter, FormInterface $form)
    {
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }
        if (!$form->isSubmitted()) {
            return;
        }
        $data = $form->getData();
        if (null === $data) {
            return;
        }

        $qb = $queryHandler->getQueryBuilder();
        $dql = [];
        foreach ($this->getFullAttributeReferences($filter, $queryHandler->getAlias()) as $column) {
            $uid = uniqid('text', false);
            $dql[] = "{$column} LIKE :{$uid}";
            $qb->setParameter($uid, '%'.$data.'%');
        }
        if (0 < count($dql)) {
            $qb->andWhere(implode(' OR ', $dql));
        }
    }
}
