<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/** One sort column. Not a criterion by itself: it only exists inside a `Sort`. */
final readonly class OrderBy
{
    public Field $field;

    /**
     * @param array<string, array<string, mixed>> $providerOptions options of the sort column, per provider name
     */
    public function __construct(Field|string $field, public SortDirection $direction = SortDirection::Asc, public array $providerOptions = [])
    {
        $this->field = Field::of($field);
    }
}
