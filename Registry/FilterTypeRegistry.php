<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Registry;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;
use UnexpectedValueException;

/**
 * Registry for filter types
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FilterTypeRegistry
{
    /** @var FilterTypeInterface[][] */
    protected $filterTypes = [];

    /**
     * @param FilterTypeInterface $filterType
     */
    public function addFilterType(FilterTypeInterface $filterType): void
    {
        $this->filterTypes[$filterType->getProvider()][$filterType->getName()] = $filterType;
    }

    /**
     * @param string $provider
     *
     * @throws UnexpectedValueException
     *
     * @return FilterTypeInterface[]
     */
    public function getFilterTypes(string $provider): array
    {
        if (!array_key_exists($provider, $this->filterTypes)) {
            throw new UnexpectedValueException("No filter types for provider with code : {$provider}");
        }

        return $this->filterTypes[$provider];
    }

    /**
     * @param string $provider
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return FilterTypeInterface
     */
    public function getFilterType(string $provider, string $code): FilterTypeInterface
    {
        if (!$this->hasFilterType($provider, $code)) {
            $flattenedTypes = implode("', '", array_keys($this->filterTypes[$provider]));
            $m = "No type for provider {$provider} with code : {$code}, ";
            $m .= "available types are '{$flattenedTypes}'.";
            throw new UnexpectedValueException($m);
        }

        return $this->filterTypes[$provider][$code];
    }

    /**
     * @param string $provider
     * @param string $code
     *
     * @return bool
     */
    public function hasFilterType(string $provider, string $code): bool
    {
        return !empty($this->filterTypes[$provider][$code]);
    }
}
