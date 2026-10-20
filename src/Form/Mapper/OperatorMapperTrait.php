<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Form\Type\OperatorValueType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * For filter types with an `operators` option: a single operator is a plain value field, several operators an
 * operator choice next to the value field. The normalised value is `{operator, value}`.
 */
trait OperatorMapperTrait
{
    /**
     * @param class-string         $valueType
     * @param array<string, mixed> $valueOptions
     */
    private function addOperatorField(FormBuilderInterface $builder, FilterDefinition $filter, string $valueType, array $valueOptions, mixed $data): void
    {
        $operators = $filter->options['operators'];
        if (1 === \count($operators)) {
            $this->addField($builder, $filter, $valueType, $valueOptions, $data);

            return;
        }
        $this->addField($builder, $filter, OperatorValueType::class, [
            'operators' => $operators,
            'value_type' => $valueType,
            'value_options' => $valueOptions,
        ], $data);
    }

    private function operatorToFormData(FilterDefinition $filter, mixed $normalized, \Closure $value): mixed
    {
        if (!\is_array($normalized)) {
            return null;
        }
        $formValue = null === $normalized['value'] ? null : $value($normalized['value']);

        return 1 === \count($filter->options['operators']) ? $formValue : ['operator' => $normalized['operator'], 'value' => $formValue];
    }
}
