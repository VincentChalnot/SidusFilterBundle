<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FilterTypeTestCase extends TestCase
{
    /**
     * @param array<string, mixed> $options
     * @param list<string>         $attributes
     */
    protected function definition(string $type, array $options = [], array $attributes = ['field']): FilterDefinition
    {
        $filterType = FilterTypeRegistry::builtIn()->get($type);
        $resolver = new OptionsResolver();
        $filterType->configureOptions($resolver);

        return new FilterDefinition('f', $type, $attributes, options: $resolver->resolve($options));
    }

    protected function type(string $type): FilterTypeInterface
    {
        return FilterTypeRegistry::builtIn()->get($type);
    }

    /**
     * Normalises then builds: the criterion, null when inactive, or the list of violation paths.
     *
     * @return Criterion|null|list<string>
     */
    protected function apply(FilterDefinition $filter, mixed $raw): Criterion|array|null
    {
        $type = $this->type($filter->type);
        $normalized = $type->normalize($filter, $raw);
        if (!$normalized->isValid()) {
            return array_map(static fn ($violation): string => $violation->path, $normalized->violations);
        }

        return $normalized->isEmpty() ? null : $type->buildCriterion($filter, $normalized->value);
    }
}
