<?php

namespace Sidus\FilterBundle\Exception;

use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Thrown when trying to access a missing filter
 */
class BadQueryHandlerException extends \LogicException
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
