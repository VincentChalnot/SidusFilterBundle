<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A checkbox for the flag filters `null`, `not_null` and `not_empty` (one service per type name).
 */
final class FlagMapper extends AbstractFormFilterMapper
{
    public function __construct(private readonly string $filterType)
    {
    }

    public function getFilterType(): string
    {
        return $this->filterType;
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void
    {
        $this->addField($builder, $filter, CheckboxType::class, [], true === $data);
    }
}
