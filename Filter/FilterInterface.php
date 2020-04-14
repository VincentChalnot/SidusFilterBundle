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
 * Base logic common to all filter systems
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
     * @return mixed
     */
    public function getDefault();

    /**
     * @param mixed $value
     */
    public function setDefault($value);

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

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $key, $default = null);
}
