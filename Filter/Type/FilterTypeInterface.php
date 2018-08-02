<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Base logic common to all filter types
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface FilterTypeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getFormType(): string;

    /**
     * @param QueryHandlerInterface $queryHandler
     * @param FilterInterface       $filter
     * @param mixed                 $data
     *
     * @throws \Sidus\FilterBundle\Exception\BadQueryHandlerException
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data);

    /**
     * @param QueryHandlerInterface $queryHandler
     * @param FilterInterface       $filter
     *
     * @throws \Sidus\FilterBundle\Exception\BadQueryHandlerException
     *
     * @return array
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array;

    /**
     * @return string
     */
    public function getProvider(): string;
}
