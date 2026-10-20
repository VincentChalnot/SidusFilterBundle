<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ExactMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return 'exact';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void
    {
        $this->addField($builder, $filter, TextType::class, [], null === $data ? null : (string) $data);
    }
}
