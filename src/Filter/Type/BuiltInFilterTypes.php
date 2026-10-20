<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterTypeInterface;

/**
 * The filter types shipped by the core, by name.
 */
final class BuiltInFilterTypes
{
    /**
     * @return array<string, class-string<FilterTypeInterface>>
     */
    public static function classes(): array
    {
        $classes = [
            TextFilterType::class, ExactFilterType::class, ChoiceFilterType::class, NumberFilterType::class,
            DateRangeFilterType::class, NullFilterType::class, NotNullFilterType::class, NotEmptyFilterType::class,
            RegexpFilterType::class, SortFilterType::class, NativeFilterType::class,
        ];

        return array_combine(array_map(static fn (string $class): string => $class::getName(), $classes), $classes);
    }
}
