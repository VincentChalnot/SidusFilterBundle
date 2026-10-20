<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class NativeCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return Native::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var Native $criterion */
        if (InMemoryQueryHandlerFactory::PROVIDER !== $criterion->provider) {
            throw new \LogicException(\sprintf('A native criterion for provider "%s" cannot be applied by the in_memory provider.', $criterion->provider));
        }
        if (!\is_callable($criterion->payload)) {
            throw new \LogicException('The payload of an in_memory native criterion must be a callable(item): bool.');
        }
        $payload = $criterion->payload;

        return static fn (mixed $item): bool => (bool) $payload($item);
    }
}
