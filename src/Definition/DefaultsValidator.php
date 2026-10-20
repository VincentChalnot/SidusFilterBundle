<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Definition;

use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

/**
 * Checks that every configured `default` is valid for its filter type, with the real filter type instances (they
 * may use injected services, which is why this is not part of the compile-time resolver). Run by the cache warmer.
 */
final readonly class DefaultsValidator
{
    public function __construct(private FilterTypeRegistry $filterTypes)
    {
    }

    /**
     * @throws ConfigurationException on the first invalid default
     */
    public function validate(ListDefinition $list): void
    {
        foreach ($list->filters as $filter) {
            if (null === $filter->default) {
                continue;
            }
            $normalized = $this->filterTypes->get($filter->type)->normalize($filter, $filter->default);
            if (!$normalized->isValid()) {
                throw new ConfigurationException(\sprintf(
                    'List "%s", filter "%s" (type "%s"): the default value is invalid: %s',
                    $list->code,
                    $filter->key,
                    $filter->type,
                    $normalized->violations[0]->message,
                ));
            }
        }
    }
}
