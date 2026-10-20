<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Exception;

final class UnknownFilterTypeException extends \LogicException
{
    /**
     * @param list<string> $available
     */
    public function __construct(string $name, array $available)
    {
        parent::__construct(\sprintf('Unknown filter type "%s", available: %s.', $name, implode(', ', $available) ?: '(none)'));
    }
}
