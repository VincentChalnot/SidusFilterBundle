<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\SidusFilterBundle;
use Sidus\FilterBundle\Tests\App\TestKernel;

final class BundleBootTest extends TestCase
{
    public function testKernelBootsWithTheBundle(): void
    {
        $kernel = new TestKernel(id: 'boot');
        $kernel->boot();

        self::assertInstanceOf(SidusFilterBundle::class, $kernel->getBundle('SidusFilterBundle'));
        $kernel->shutdown();
    }
}
