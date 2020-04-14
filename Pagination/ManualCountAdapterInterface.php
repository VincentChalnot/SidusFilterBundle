<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Pagination;

/**
 * Allow to manually set the result count with a pager adapter, useful for query optimization
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ManualCountAdapterInterface
{
    /**
     * @param int $nbResult
     */
    public function setNbResults(int $nbResult);
}
