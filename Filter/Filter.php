<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Default filter implementation, you should not need to customize this class
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Filter implements FilterInterface
{
    /** @var QueryHandlerConfigurationInterface */
    protected $queryHandlerConfiguration;

    /** @var string */
    protected $code;

    /** @var string */
    protected $filterType;

    /** @var array */
    protected $attributes = [];

    /** @var string */
    protected $formType;

    /** @var string */
    protected $label;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $formOptions = [];

    /** @var mixed */
    protected $default;

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     * @param string                             $code
     * @param string                             $filterType
     * @param array                              $attributes
     * @param string                             $formType
     * @param string                             $label
     * @param array                              $options
     * @param array                              $formOptions
     * @param null                               $default
     */
    public function __construct(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration,
        string $code,
        string $filterType,
        array $attributes = [],
        string $formType = null,
        string $label = null,
        array $options = [],
        array $formOptions = [],
        $default = null
    ) {
        $this->queryHandlerConfiguration = $queryHandlerConfiguration;
        $this->code = $code;
        $this->filterType = $filterType;
        $this->attributes = $attributes;
        $this->formType = $formType;
        $this->label = $label;
        $this->options = $options;
        $this->formOptions = $formOptions;
        $this->default = $default;

        if (empty($attributes)) {
            $this->attributes = [$code];
        }
    }

    /**
     * @return QueryHandlerConfigurationInterface
     */
    public function getQueryHandlerConfiguration(): QueryHandlerConfigurationInterface
    {
        return $this->queryHandlerConfiguration;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFilterType(): string
    {
        return $this->filterType;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return $this->options[$key];
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }
}
