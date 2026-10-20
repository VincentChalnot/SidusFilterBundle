<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\InMemory\InMemoryCriterionCompilerInterface;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;

abstract class AbstractInMemoryCompiler implements InMemoryCriterionCompilerInterface
{
    public static function getProvider(): string
    {
        return InMemoryQueryHandlerFactory::PROVIDER;
    }
}
