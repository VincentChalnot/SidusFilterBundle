<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Exception;

final class UnknownProviderException extends \LogicException
{
    /**
     * @param list<string> $available
     */
    public function __construct(string $provider, array $available)
    {
        parent::__construct(\sprintf('Unknown provider "%s", available: %s.', $provider, implode(', ', $available) ?: '(none)'));
    }
}
