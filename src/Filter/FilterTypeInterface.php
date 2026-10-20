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
 * Two halves, like {@see \Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface}: the *static* methods
 * ({@see getName()}, {@see configureOptions()}, {@see getRequiredOperations()}) describe the type at container compile
 * time, when no service exists; the *instance* methods ({@see normalize()}, {@see buildCriterion()}) run per request
 * and may use injected services. Defaults are validated at cache warm-up with the real instances.
 */
interface FilterTypeInterface
{
    public static function getName(): string;

    /** Validates the filter's own `options` at compile time. */
    public static function configureOptions(OptionsResolver $resolver): void;

    /**
     * Every criterion class this filter can emit for this definition (composites included): capability checks.
     *
     * @return list<class-string<Criterion>>
     */
    public static function getRequiredOperations(FilterDefinition $filter): array;

    /**
     * Raw input (query string, JSON, form, configured default) to a normalised value, "empty", or violations.
     * Never throws on user input. Also called with `null` to obtain what an absent input means (e.g. the default sort),
     * and at cache warm-up with each configured `default`.
     */
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue;

    /** Normalised value to criterion; null when the filter is inactive. */
    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion;
}
