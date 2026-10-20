<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

final readonly class AllOf implements Criterion
{
    /** @var list<Criterion> */
    public array $criteria;

    public function __construct(Criterion ...$criteria)
    {
        if ([] === $criteria) {
            throw new \InvalidArgumentException('AllOf requires at least one criterion.');
        }
        $this->criteria = array_values($criteria);
    }
}
