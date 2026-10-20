<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\CacheWarmer;

use Sidus\FilterBundle\Definition\DefaultsValidator;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Fails `cache:warmup` (so deploys and CI) on an invalid `default:` of any filter. Not optional: a bad default would
 * otherwise only show up as a violation on the first request that relies on it.
 */
final readonly class DefaultsWarmer implements CacheWarmerInterface
{
    public function __construct(
        private ListDefinitionRegistry $definitions,
        private DefaultsValidator $validator,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->definitions->codes() as $code) {
            $this->validator->validate($this->definitions->get($code));
        }

        return [];
    }
}
