<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Exception;

use LogicException;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Thrown when trying to access a missing filter
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class BadQueryHandlerException extends LogicException
{
    /**
     * @param QueryHandlerInterface $queryHandler
     * @param string                $class
     */
    public function __construct(QueryHandlerInterface $queryHandler, string $class)
    {
        parent::__construct("Query handler {$queryHandler->getConfiguration()->getCode()} must implements {$class}");
    }
}
