<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

final class NumberMapper extends AbstractFormFilterMapper
{
    use OperatorMapperTrait;

    public function getFilterType(): string
    {
        return 'number';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, mixed $data): void
    {
        $this->addOperatorField($builder, $filter, NumberType::class, ['html5' => true, 'input' => 'string', 'scale' => null], $data);
    }

    public function toFormData(FilterDefinition $filter, mixed $normalized): mixed
    {
        return $this->operatorToFormData($filter, $normalized, static fn (int|float $value): string => (string) $value);
    }
}
