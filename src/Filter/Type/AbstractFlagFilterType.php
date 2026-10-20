<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Input\Violation;

/**
 * A boolean flag: active when truthy, inactive when falsy.
 */
abstract class AbstractFlagFilterType extends AbstractFilterType
{
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (null === $raw || false === $raw || \in_array($raw, ['', '0', 0, 'false', 'off', 'no'], true)) {
            return NormalizedValue::empty();
        }
        if (true === $raw || \in_array($raw, ['1', 1, 'true', 'on', 'yes'], true)) {
            return NormalizedValue::of(true);
        }

        return NormalizedValue::invalid(new Violation('', 'Expected a boolean flag.', $raw));
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return true === $value ? $this->forAttributes($filter, $this->criterionFactory($filter->providerOptions)) : null;
    }

    /**
     * @param array<string, array<string, mixed>> $providerOptions the filter's `provider_options`
     *
     * @return \Closure(\Sidus\FilterBundle\Definition\Field): Criterion
     */
    abstract protected function criterionFactory(array $providerOptions): \Closure;
}
