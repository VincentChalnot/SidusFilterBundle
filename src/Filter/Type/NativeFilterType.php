<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Psr\Container\ContainerInterface;
use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NativeCriterionBuilderInterface;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Escape hatch: any non-blank input is handed as is to the {@see NativeCriterionBuilderInterface} service named by the
 * `builder` option, which returns a {@see Native} criterion. Restrict it with `providers:` to the provider it targets.
 */
final class NativeFilterType extends AbstractFilterType
{
    public function __construct(private readonly ?ContainerInterface $builders = null)
    {
    }

    public static function getName(): string
    {
        return 'native';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('builder');
        $resolver->setAllowedTypes('builder', 'string');
    }

    public function getRequiredOperations(FilterDefinition $filter): array
    {
        return [Native::class];
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        return self::isBlank($raw) ? NormalizedValue::empty() : NormalizedValue::of($raw);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        $id = $filter->options['builder'];
        if (null === $this->builders || !$this->builders->has($id)) {
            throw new \LogicException(\sprintf('Native filter "%s": no builder service "%s" (tag it with sidus_filter.native_builder).', $filter->key, $id));
        }

        return $this->builders->get($id)($filter, $value);
    }
}
