<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter;

use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;

/**
 * Base logic common to all filter systems
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface FilterInterface
{
    public function getQueryHandlerConfiguration(): QueryHandlerConfigurationInterface;

    public function getCode(): string;

    public function getAttributes(): array;

    public function getFilterType(): string;

    public function getLabel(): ?string;

    public function getOptions(): array;

    public function getDefault(): mixed;

    public function setDefault(mixed $value);

    /**
     * Override form type from default filter type
     */
    public function getFormType(): ?string;

    public function getFormOptions(): array;

    public function getOption(string $key, mixed $default = null): mixed;
}
