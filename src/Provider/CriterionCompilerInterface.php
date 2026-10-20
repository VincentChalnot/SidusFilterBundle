<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Criterion\Criterion;

/**
 * Base of the per-provider compiler interfaces: realises one criterion class in the provider's native query.
 * Provider packages define their own sub-interface (with the `compile` signature that suits their query) and tag
 * implementations (autoconfigured). Both methods are read at compile time without instantiating the service.
 */
interface CriterionCompilerInterface
{
    public static function getProvider(): string;

    /** @return class-string<Criterion> */
    public static function getCriterionClass(): string;
}
