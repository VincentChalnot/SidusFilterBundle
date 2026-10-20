<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

/**
 * Same type name as its parent: registering both is a compile-time error.
 */
final class TwinPrefixedFilterType extends PrefixedFilterType
{
}
