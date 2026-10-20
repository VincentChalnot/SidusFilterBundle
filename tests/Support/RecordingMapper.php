<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Form\Mapper\AbstractFormFilterMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A mapper for the `prefixed` test type that derives a field attribute from the list's provider options.
 */
final class RecordingMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return 'prefixed';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void
    {
        $this->addField($builder, $filter, TextType::class, ['attr' => ['data-list' => $list->code, 'data-source' => $list->options['source']]], $data);
    }
}
