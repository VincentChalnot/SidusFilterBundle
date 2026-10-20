<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\InMemory\InMemorySourceInterface;
use Sidus\FilterBundle\Test\Contract\FixtureDataset;

final class PostsSource implements InMemorySourceInterface
{
    public function items(): iterable
    {
        return FixtureDataset::posts();
    }
}
