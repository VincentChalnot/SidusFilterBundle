<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Form\Type\SortType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The sort filter as a field holding the API-style value (`-date,title`) and one button per column key.
 */
final class SortMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return 'sort';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, mixed $data): void
    {
        $this->addField($builder, $filter, SortType::class, ['columns' => array_keys($filter->options['columns'])], $data);
    }

    /** @param list<array{column: string, direction: string}> $normalized */
    public function toFormData(FilterDefinition $filter, mixed $normalized): mixed
    {
        return implode(',', array_map(static fn (array $item): string => ('desc' === $item['direction'] ? '-' : '').$item['column'], $normalized));
    }
}
