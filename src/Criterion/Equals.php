<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/** Strictly equal after type normalisation: strings case-sensitive, dates as instants, numbers numerically. */
final readonly class Equals extends FieldCriterion
{
    public function __construct(Field|string $field, public mixed $value)
    {
        parent::__construct($field);
    }
}
