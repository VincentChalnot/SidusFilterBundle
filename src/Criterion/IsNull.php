<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/** The value is null or the property is absent; for a to-one relation: no related entity. */
final readonly class IsNull extends FieldCriterion
{
}
