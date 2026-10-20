<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Definition;

use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Exception\UnknownFilterTypeException;
use Sidus\FilterBundle\Provider\Capabilities;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Turns the processed `sidus_filter.lists.<code>` configuration into a {@see ListDefinition}, validating everything
 * that does not need runtime services (section 7.2): provider, options, filter types, attributes, defaults, public
 * names, and the capability table of the provider (section 6). Pure: no container, no I/O.
 *
 * Every failure is a {@see ConfigurationException} naming the list, the filter and the reason.
 */
final readonly class DefinitionResolver
{
    /**
     * @param array<string, class-string<QueryHandlerFactoryInterface>> $factories    provider => factory class
     * @param array<string, Capabilities>                                $capabilities provider => capabilities
     */
    public function __construct(
        private FilterTypeRegistry $filterTypes,
        private array $factories,
        private array $capabilities,
    ) {
    }

    /**
     * @param array<string, mixed> $config provider, options, pagination, count, filters
     */
    public function resolve(string $code, array $config): ListDefinition
    {
        $provider = $config['provider'] ?? null;
        if (!\is_string($provider) || '' === $provider) {
            throw new ConfigurationException(\sprintf('List "%s": the "provider" is required.', $code));
        }
        $factory = $this->factories[$provider] ?? throw new ConfigurationException(\sprintf(
            'List "%s": unknown provider "%s" (the provider must be known at compile time), available: %s.',
            $code,
            $provider,
            implode(', ', array_keys($this->factories)) ?: '(none)',
        ));
        $capabilities = $this->capabilities[$provider] ?? new Capabilities($provider, []);

        $options = $this->resolveOptions(\sprintf('List "%s": options', $code), $factory::configureListOptions(...), $config['options'] ?? []);
        $perPage = $config['pagination']['per_page'] ?? 15;
        $maxPerPage = $config['pagination']['max_per_page'] ?? 100;
        if ($perPage < 1 || $maxPerPage < $perPage) {
            throw new ConfigurationException(\sprintf('List "%s": pagination requires 1 <= per_page (%d) <= max_per_page (%d).', $code, $perPage, $maxPerPage));
        }
        $count = $config['count'] ?? ListDefinition::COUNT_AUTO;
        if (!\is_string($count) || '' === $count) {
            throw new ConfigurationException(\sprintf('List "%s": "count" must be "auto", "none" or a service id.', $code));
        }

        $filters = [];
        $publicNames = [];
        $sortFilters = [];
        foreach ($config['filters'] ?? [] as $key => $filterConfig) {
            $key = (string) $key;
            $providers = $filterConfig['providers'] ?? [];
            if ([] !== $providers && !\in_array($provider, $providers, true)) {
                continue; // provider-specific filter for another provider: ignored entirely
            }
            $filter = $this->resolveFilter($code, $key, $filterConfig, $factory);
            $name = $filter->publicName();
            if (isset($publicNames[$name])) {
                throw new ConfigurationException(\sprintf(
                    'List "%s": filters "%s" and "%s" have the same public name "%s"; give one of them a distinct "name" or restrict it with "providers".',
                    $code,
                    $publicNames[$name],
                    $key,
                    $name,
                ));
            }
            $publicNames[$name] = $key;
            $this->checkCapabilities($code, $filter, $capabilities, $sortFilters);
            $filters[] = $filter;
        }
        if (\count($sortFilters) > 1) {
            throw new ConfigurationException(\sprintf('List "%s": at most one sort filter is allowed, found: %s.', $code, implode(', ', $sortFilters)));
        }

        return new ListDefinition($code, $provider, $options, $perPage, $maxPerPage, $count, $filters);
    }

    /**
     * @param array<string, mixed>                                         $filterConfig
     * @param class-string<QueryHandlerFactoryInterface> $factory
     */
    private function resolveFilter(string $code, string $key, array $filterConfig, string $factory): FilterDefinition
    {
        $where = \sprintf('List "%s", filter "%s"', $code, $key);
        $typeName = $filterConfig['type'] ?? null;
        if (!\is_string($typeName) || '' === $typeName) {
            throw new ConfigurationException($where.': the "type" is required.');
        }
        try {
            $type = $this->filterTypes->get($typeName);
        } catch (UnknownFilterTypeException $e) {
            throw new ConfigurationException($where.': '.$e->getMessage(), previous: $e);
        }
        $where .= \sprintf(' (type "%s")', $typeName);

        $options = $this->resolveOptions($where.': options', $type->configureOptions(...), $filterConfig['options'] ?? []);
        $provider = $factory::getProvider();
        $providerOptions = $filterConfig['provider_options'] ?? [];
        if (isset($providerOptions[$provider])) {
            $providerOptions[$provider] = $this->resolveOptions($where.': provider_options.'.$provider, $factory::configureFilterOptions(...), $providerOptions[$provider]);
        }
        try {
            $attributes = array_map(Field::fromConfig(...), $filterConfig['attributes'] ?? []);
        } catch (\InvalidArgumentException $e) {
            throw new ConfigurationException($where.': '.$e->getMessage(), previous: $e);
        }
        $default = $filterConfig['default'] ?? null;
        $locked = (bool) ($filterConfig['locked'] ?? false);
        if ($locked && null === $default) {
            throw new ConfigurationException($where.': a locked filter requires a "default" (the fixed value).');
        }

        $filter = new FilterDefinition(
            key: $key,
            type: $typeName,
            attributes: $attributes,
            name: $filterConfig['name'] ?? null,
            providers: $filterConfig['providers'] ?? [],
            options: $options,
            providerOptions: $providerOptions,
            locked: $locked,
            default: $default,
            form: $filterConfig['form'] ?? [],
        );
        if (null !== $default) {
            $normalized = $type->normalize($filter, $default);
            if (!$normalized->isValid()) {
                throw new ConfigurationException(\sprintf('%s: the default value is invalid: %s', $where, $normalized->violations[0]->message));
            }
        }

        return $filter;
    }

    /**
     * @param list<string> $sortFilters collects the keys of the filters emitting a Sort
     */
    private function checkCapabilities(string $code, FilterDefinition $filter, Capabilities $capabilities, array &$sortFilters): void
    {
        $type = $this->filterTypes->get($filter->type);
        foreach ($type->getRequiredOperations($filter) as $criterionClass) {
            if (Sort::class === $criterionClass) {
                $sortFilters[] = $filter->key;
            }
            if (null !== ($reason = $capabilities->supports($criterionClass))) {
                throw new ConfigurationException(\sprintf(
                    'List "%s", filter "%s" (type "%s"): criterion %s is not supported: %s. Narrow the filter (e.g. options.operators) or restrict it with "providers".',
                    $code,
                    $filter->key,
                    $filter->type,
                    $criterionClass,
                    $reason,
                ));
            }
        }
    }

    /**
     * @param \Closure(OptionsResolver): void $configure
     * @param array<string, mixed>            $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(string $where, \Closure $configure, array $options): array
    {
        $resolver = new OptionsResolver();
        $configure($resolver);
        try {
            return $resolver->resolve($options);
        } catch (OptionsException|\InvalidArgumentException $e) {
            throw new ConfigurationException($where.': '.$e->getMessage(), previous: $e);
        }
    }
}
