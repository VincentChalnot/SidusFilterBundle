<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Base logic common to all filter systems
 */
interface FilterInterface
{
    /**
     * @return QueryHandlerConfigurationInterface
     */
    public function getQueryHandlerConfiguration(): QueryHandlerConfigurationInterface;

    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @return string
     */
    public function getFilterType(): string;

    /**
     * @return string|null
     */
    public function getLabel();

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * Override form type from default filter type
     *
     * @return string|null
     */
    public function getFormType();

    /**
     * @return array
     */
    public function getFormOptions(): array;
}
