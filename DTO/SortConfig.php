<?php

namespace Sidus\FilterBundle\DTO;

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

    /**
     * @return string
     */
    public function getDefaultColumn()
    {
        return $this->defaultColumn;
    }

    /**
     * @param string $defaultColumn
     */
    public function setDefaultColumn($defaultColumn)
    {
        $this->defaultColumn = $defaultColumn;
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
     */
    public function setDefaultDirection($defaultDirection)
    {
        $this->defaultDirection = $defaultDirection;
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
     */
    public function setColumn($column)
    {
        $this->column = $column;
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
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }

    public function switchDirection()
    {
        $this->direction = !$this->direction;
    }
}
