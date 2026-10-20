<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Criterion\Criterion;

/**
 * What a provider realises with the reference meaning (section 6: strict, no approximation).
 */
final readonly class Capabilities
{
    /** @var array<class-string<Criterion>, true> */
    private array $supported;

    /**
     * @param list<class-string<Criterion>>        $supported
     * @param array<class-string<Criterion>,string> $refusals  explicit refusal reasons, they win over `$supported`
     */
    public function __construct(public string $provider, array $supported, private array $refusals = [])
    {
        $this->supported = array_fill_keys($supported, true);
    }

    /**
     * @param iterable<class-string<CriterionCompilerInterface>> $compilerClasses
     */
    public static function fromCompilers(string $provider, iterable $compilerClasses): self
    {
        $supported = [];
        foreach ($compilerClasses as $class) {
            $supported[] = $class::getCriterionClass();
        }

        return new self($provider, $supported);
    }

    /** @return string|null null when supported, the refusal reason otherwise */
    public function supports(string $criterionClass): ?string
    {
        if (isset($this->refusals[$criterionClass])) {
            return $this->refusals[$criterionClass];
        }

        return isset($this->supported[$criterionClass])
            ? null
            : \sprintf('provider "%s" has no compiler for it', $this->provider);
    }

    /** @return list<class-string<Criterion>> */
    public function supportedClasses(): array
    {
        return array_keys($this->supported);
    }
}
