<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Validates and normalises the raw input of one filter and emits abstract criteria. Knows nothing about data sources
 * nor about Symfony Forms. Registered once by name, autoconfigured.
 *
 * Contract: {@see configureOptions()}, {@see getRequiredOperations()} and {@see normalize()} are pure (no injected
 * service): the compiler pass calls them on an instance created without its constructor, at compile time.
 */
interface FilterTypeInterface
{
    public static function getName(): string;

    /** Validates the filter's own `options` at compile time. */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * Every criterion class this filter can emit for this definition (composites included): capability checks.
     *
     * @return list<class-string<Criterion>>
     */
    public function getRequiredOperations(FilterDefinition $filter): array;

    /**
     * Raw input (query string, JSON, form, configured default) to a normalised value, "empty", or violations.
     * Never throws on user input. Also called with `null` to obtain what an absent input means (e.g. the default sort).
     */
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue;

    /** Normalised value to criterion; null when the filter is inactive. */
    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion;
}
