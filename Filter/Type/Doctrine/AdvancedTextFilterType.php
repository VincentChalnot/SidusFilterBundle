<?php

namespace Sidus\FilterBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;

/**
 * Replaces the standard TextFilterType with more advance capabilities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdvancedTextFilterType extends AbstractSimpleFilterType
{
    /**
     * Must return the DQL statement and set the proper parameters in the QueryBuilder
     *
     * @param QueryBuilder $qb
     * @param string       $column
     * @param mixed        $data
     *
     * @return string
     */
    protected function applyDQL(QueryBuilder $qb, string $column, $data): string
    {
        $input = $data['input'];
        $uid = uniqid('text', false); // Generate random parameter names to prevent collisions
        switch ($data['option']) {
            case 'exact':
                $qb->setParameter($uid, $input);

                return "{$column} = :{$uid}";
            case 'like_':
                $qb->setParameter($uid, trim($input, '%').'%');

                return "{$column} LIKE :{$uid}";
            case '_like':
                $qb->setParameter($uid, '%'.trim($input, '%'));

                return "{$column} LIKE :{$uid}";
            case '_like_':
                $qb->setParameter($uid, '%'.trim($input, '%').'%');

                return "{$column} LIKE :{$uid}";
            case 'notlike_':
                $qb->setParameter($uid, trim($input, '%').'%');

                return "{$column} NOT LIKE :{$uid}";
            case '_notlike':
                $qb->setParameter($uid, '%'.trim($input, '%'));

                return "{$column} NOT LIKE :{$uid}";
            case '_notlike_':
                $qb->setParameter($uid, '%'.trim($input, '%').'%');

                return "{$column} NOT LIKE :{$uid}";
            case 'empty':
                return "{$column} = ''";
            case 'notempty':
                return "{$column} != ''";
            case 'null':
                return "{$column} IS NULL";
            case 'notnull':
                return "{$column} IS NOT NULL";
        }
        throw new \UnexpectedValueException("Unknown option '{$data['option']}'");
    }
}
