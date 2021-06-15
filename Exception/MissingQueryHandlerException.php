<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Exception;

use UnexpectedValueException;

/**
 * Thrown when trying to access a missing filter
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingQueryHandlerException extends UnexpectedValueException
{
    /**
     * @param string $code
     */
    public function __construct(string $code)
    {
        parent::__construct("No query handler with code: {$code}");
    }
}
