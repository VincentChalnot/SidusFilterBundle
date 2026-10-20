<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

final readonly class Not implements Criterion
{
    public function __construct(public Criterion $criterion)
    {
    }
}
