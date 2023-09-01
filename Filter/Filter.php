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
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Default filter implementation, you should not need to customize this class
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Filter implements FilterInterface
{
    public function __construct(
        protected QueryHandlerConfigurationInterface $queryHandlerConfiguration,
        protected string $code,
        protected string $filterType,
        protected array $attributes = [],
        protected ?string $formType = null,
        protected ?string $label = null,
        protected array $options = [],
        protected array $formOptions = [],
        protected mixed $default = null
    ) {
        if (empty($attributes)) {
            $this->attributes = [$code];
        }
    }

    public function getQueryHandlerConfiguration(): QueryHandlerConfigurationInterface
    {
        return $this->queryHandlerConfiguration;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getFilterType(): string
    {
        return $this->filterType;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return $this->options[$key];
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setDefault(mixed $value): void
    {
        $this->default = $value;
    }
}
