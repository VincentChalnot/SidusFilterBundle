<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/** Escape hatch: a payload only the named provider understands. Other providers refuse it. */
final readonly class Native implements Criterion
{
    public function __construct(public string $provider, public mixed $payload)
    {
    }
}
