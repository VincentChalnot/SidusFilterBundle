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

namespace Sidus\FilterBundle\DTO;

/**
 * This class carries the configuration for sorting data in the filter configuration handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SortConfig
{
    protected ?string $column;

    protected ?bool $direction;

    protected int $page = 1;

    public function __construct(
        protected ?string $defaultColumn = null,
        protected bool $defaultDirection = false,
    ) {
    }

    public function getColumn(): ?string
    {
        return $this->column ?? $this->defaultColumn;
    }

    public function setColumn(string $column = null): void
    {
        $this->column = $column;
    }

    public function getDirection(): bool
    {
        return $this->direction ?? $this->defaultDirection;
    }

    public function setDirection(bool $direction = null): void
    {
        if (null !== $direction) {
            $this->direction = $direction;
        }
    }

    /**
     * Reverse search direction
     */
    public function switchDirection(): void
    {
        $this->direction = !$this->direction;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page = null): void
    {
        if (null !== $page) {
            $this->page = $page;
        }
    }
}
