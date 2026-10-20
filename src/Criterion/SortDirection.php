<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
