<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

final readonly class AnyOf implements Criterion
{
    /** @var list<Criterion> */
    public array $criteria;

    public function __construct(Criterion ...$criteria)
    {
        if ([] === $criteria) {
            throw new \InvalidArgumentException('AnyOf requires at least one criterion.');
        }
        $this->criteria = array_values($criteria);
    }
}
