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
     * @return string
     */
    public function getDefaultColumn()
    {
        return $this->defaultColumn;
    }

    /**
     * @param string $defaultColumn
     * @return SortConfig
     */
    public function setDefaultColumn($defaultColumn)
    {
        $this->defaultColumn = $defaultColumn;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDefaultDirection()
    {
        return $this->defaultDirection;
    }

    /**
     * @param boolean $defaultDirection
     * @return SortConfig
     */
    public function setDefaultDirection($defaultDirection)
    {
        $this->defaultDirection = $defaultDirection;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        if (null === $this->column) {
            return $this->getDefaultColumn();
        }

        return $this->column;
    }

    /**
     * @param string $column
     * @return SortConfig
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDirection()
    {
        if (null === $this->direction) {
            return $this->getDefaultDirection();
        }

        return $this->direction;
    }

    /**
     * @param boolean $direction
     * @return SortConfig
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Reverse search direction
     * @return SortConfig
     */
    public function switchDirection()
    {
        $this->direction = !$this->direction;

        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return SortConfig
     */
    public function setPage($page)
    {
        $page = (int) $page;
        $this->page = $page ?: 1;

        return $this;
    }
}
