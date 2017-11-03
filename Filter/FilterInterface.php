<?php

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Filter\Type\FilterTypeInterface;

/**
 * Base logic common to all filter systems
 */
interface FilterInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @return FilterTypeInterface
     */
    public function getFilterType(): FilterTypeInterface;

    /**
     * @return string|null
     */
    public function getLabel();

    /**
     * @param string $label
     */
    public function setLabel(string $label);

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * Override form type from default filter type
     *
     * @return string
     */
    public function getFormType(): string;

    /**
     * @param string $formType
     */
    public function setFormType(string $formType);

    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions);

    /**
     * @return array
     */
    public function getFormOptions(): array;

    /**
     * @return string
     */
    public function getProvider(): string;
}
