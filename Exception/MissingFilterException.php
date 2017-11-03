<?php

namespace Sidus\FilterBundle\Exception;

/**
 * Thrown when trying to access a missing filter
 */
class MissingFilterException extends \UnexpectedValueException
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No filter with code : {$code}");
    }
}
