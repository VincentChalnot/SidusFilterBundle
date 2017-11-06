<?php

namespace Sidus\FilterBundle\Exception;

/**
 * Thrown when trying to access a missing filter
 */
class MissingQueryHandlerFactoryException extends \UnexpectedValueException
{
    /**
     * @param string $provider
     */
    public function __construct(string $provider)
    {
        parent::__construct("No query handler factory for provider: {$provider}");
    }
}
