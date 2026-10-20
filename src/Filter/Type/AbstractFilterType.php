<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Criterion\AnyOf;
use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilterType implements FilterTypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    protected static function isBlank(mixed $raw): bool
    {
        return null === $raw || '' === $raw || [] === $raw;
    }

    /**
     * One criterion per attribute, combined with `AnyOf` when the filter has several attributes (OR behaviour).
     *
     * @param \Closure(Field): Criterion $build
     */
    protected function forAttributes(FilterDefinition $filter, \Closure $build): Criterion
    {
        $criteria = array_map($build, $filter->attributes);

        return 1 === \count($criteria) ? $criteria[0] : new AnyOf(...$criteria);
    }

    /**
     * @param list<class-string<Criterion>> $classes
     *
     * @return list<class-string<Criterion>>
     */
    protected function withAnyOf(FilterDefinition $filter, array $classes): array
    {
        if (\count($filter->attributes) > 1) {
            $classes[] = AnyOf::class;
        }

        return array_values(array_unique($classes));
    }
}
