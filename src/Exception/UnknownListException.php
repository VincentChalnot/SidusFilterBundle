<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Exception;

final class UnknownListException extends \LogicException
{
    /**
     * @param list<string> $available
     */
    public function __construct(string $code, array $available)
    {
        parent::__construct(\sprintf('Unknown list "%s", available: %s.', $code, implode(', ', $available) ?: '(none)'));
    }
}
