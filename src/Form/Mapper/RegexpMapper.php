<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class RegexpMapper extends AbstractFormFilterMapper
{
    use OperatorMapperTrait;

    public function getFilterType(): string
    {
        return 'regexp';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void
    {
        $this->addOperatorField($builder, $filter, TextType::class, [], $data);
    }

    public function toFormData(FilterDefinition $filter, mixed $normalized): mixed
    {
        return $this->operatorToFormData($filter, $normalized, static fn (mixed $value): string => (string) $value);
    }
}
