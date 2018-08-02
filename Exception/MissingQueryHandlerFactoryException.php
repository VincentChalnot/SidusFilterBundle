<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Exception;

/**
 * Thrown when trying to access a missing filter
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
