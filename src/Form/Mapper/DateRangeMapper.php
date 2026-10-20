<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormBuilderInterface;

final class DateRangeMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return 'date_range';
    }

    /**
     * The compound view data holds the children's model data (dates): back to `Y-m-d` strings in the filter's
     * timezone so that the date filter type keeps its whole-day semantics for date-only bounds.
     *
     * @param array{from?: \DateTimeInterface|null, to?: \DateTimeInterface|null} $viewData
     */
    public function toRaw(FilterDefinition $filter, mixed $viewData): mixed
    {
        $timezone = new \DateTimeZone($filter->options['timezone']);
        $format = static fn (mixed $date): string => $date instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($date)->setTimezone($timezone)->format('Y-m-d') : '';

        return \is_array($viewData) ? ['from' => $format($viewData['from'] ?? null), 'to' => $format($viewData['to'] ?? null)] : $viewData;
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void
    {
        $this->addField($builder, $filter, DateRangeType::class, ['timezone' => $filter->options['timezone']], $data);
    }
}
