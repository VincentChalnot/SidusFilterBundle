<?php

namespace Sidus\FilterBundle\Exception;

/**
 * Thrown when trying to access a missing filter
 */
class MissingQueryHandlerException extends \UnexpectedValueException
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No query handler with code : {$code}");
    }
}
