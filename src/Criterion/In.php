<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/** `Equals` for any of the values. An empty list is rejected: it never means "match nothing". */
final readonly class In extends FieldCriterion
{
    /** @var list<mixed> */
    public array $values;

    /**
     * @param list<mixed> $values
     */
    public function __construct(Field|string $field, array $values)
    {
        if ([] === $values) {
            throw new \InvalidArgumentException('In requires at least one value.');
        }
        parent::__construct($field);
        $this->values = array_values($values);
    }
}
