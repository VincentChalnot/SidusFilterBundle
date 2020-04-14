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

use Doctrine\ORM\QueryBuilder;
use UnexpectedValueException;

/**
 * Dedicated filter for numbers
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdvancedNumberFilterType extends AbstractSimpleFilterType
{
    protected const EMPTY_OPTIONS = ['empty', 'notempty', 'null', 'notnull'];

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
        $uid = uniqid('number', false); // Generate random parameter names to prevent collisions
        switch ($data['option']) {
            case 'exact':
                $qb->setParameter($uid, $input);

                return "{$column} = :{$uid}";
            case 'greaterthan':
                $qb->setParameter($uid, $input);

                return "{$column} > :{$uid}";
            case 'lowerthan':
                $qb->setParameter($uid, $input);

                return "{$column} < :{$uid}";
            case 'greaterthanequals':
                $qb->setParameter($uid, $input);

                return "{$column} >= :{$uid}";
            case 'lowerthanequals':
                $qb->setParameter($uid, $input);

                return "{$column} <= :{$uid}";
            case 'empty':
                return "{$column} = ''";
            case 'notempty':
                return "{$column} != ''";
            case 'null':
                return "{$column} IS NULL";
            case 'notnull':
                return "{$column} IS NOT NULL";
        }
        throw new UnexpectedValueException("Unknown option '{$data['option']}'");
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    protected function isEmpty($data): bool
    {
        // Handle specific cases where input can be blank
        if (array_key_exists('option', $data) && in_array($data['option'], static::EMPTY_OPTIONS, true)) {
            return false;
        }

        return parent::isEmpty($data) || empty($data['input']);
    }
}
