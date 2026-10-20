<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Provider\Capabilities;
use Sidus\FilterBundle\Tests\Support\UninstantiableCompiler;

final class CapabilitiesTest extends TestCase
{
    public function testBuiltFromCompilerClassesWithoutInstantiatingThem(): void
    {
        $capabilities = Capabilities::fromCompilers('x', [UninstantiableCompiler::class]);

        self::assertNull($capabilities->supports(Equals::class));
        self::assertSame('provider "x" has no compiler for it', $capabilities->supports(Contains::class));
        self::assertSame([Equals::class], $capabilities->supportedClasses());
    }
}
