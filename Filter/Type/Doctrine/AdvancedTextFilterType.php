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
 * Replaces the standard TextFilterType with more advance capabilities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdvancedTextFilterType extends AbstractSimpleFilterType
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
