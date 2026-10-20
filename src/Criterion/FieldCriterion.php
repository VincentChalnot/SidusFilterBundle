<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

abstract readonly class FieldCriterion implements Criterion
{
    public Field $field;

    public function __construct(Field|string $field)
    {
        $this->field = Field::of($field);
    }
}
