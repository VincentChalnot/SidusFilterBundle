<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
