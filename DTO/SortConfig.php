<?php

namespace Sidus\FilterBundle\DTO;

/**
 * This class carries the configuration for sorting data in the filter configuration handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SortConfig
{
    /** @var string */
    protected $defaultColumn;

    /** @var bool */
    protected $defaultDirection = false;

    /** @var string */
    protected $column;

    /** @var bool */
    protected $direction;

    /** @var int */
    protected $page = 1;

    /**
     * @param string $defaultColumn
     * @param bool   $defaultDirection
     */
    public function __construct(string $defaultColumn = null, bool $defaultDirection = false)
    {
        $this->defaultColumn = $defaultColumn;
        $this->defaultDirection = $defaultDirection;
    }

    /**
     * @return string|null
     */
    public function getColumn()
    {
        if (null === $this->column) {
            return $this->defaultColumn;
        }

        return $this->column;
    }

    /**
     * @param string|null $column
     */
    public function setColumn(string $column = null)
    {
        $this->column = $column;
    }

    /**
     * @return boolean
     */
    public function getDirection(): bool
    {
        if (null === $this->direction) {
            return $this->defaultDirection;
        }

        return $this->direction;
    }

    /**
     * @param boolean $direction
     */
    public function setDirection(bool $direction)
    {
        $this->direction = $direction;
    }

    /**
     * Reverse search direction
     */
    public function switchDirection()
    {
        $this->direction = !$this->direction;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }
}
