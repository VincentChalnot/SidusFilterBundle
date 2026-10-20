<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Filter\Type\AbstractFilterType;
use Sidus\FilterBundle\Input\Violation;

/**
 * A custom filter type with a constructor dependency: the compile-time half is static, the runtime half uses the service.
 */
class PrefixedFilterType extends AbstractFilterType
{
    public function __construct(private readonly Prefixer $prefixer)
    {
    }

    public static function getName(): string
    {
        return 'prefixed';
    }

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return self::withAnyOf($filter, [Equals::class]);
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }
        if (!\is_string($raw) || !$this->prefixer->accepts($raw)) {
            return NormalizedValue::invalid(new Violation('', 'Rejected by the prefixer.', $raw));
        }

        return NormalizedValue::of($this->prefixer->apply($raw));
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $field): Equals => new Equals($field, $value));
    }
}
