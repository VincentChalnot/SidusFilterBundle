<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Form\FormFilterMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The per-filter `form:` configuration (`label`, `type`, `options`) is applied on top of the mapper's defaults.
 */
abstract class AbstractFormFilterMapper implements FormFilterMapperInterface
{
    public function toRaw(FilterDefinition $filter, mixed $viewData): mixed
    {
        return $viewData;
    }

    public function toFormData(FilterDefinition $filter, mixed $normalized): mixed
    {
        return $normalized;
    }

    /**
     * @param class-string         $defaultType
     * @param array<string, mixed> $defaultOptions
     */
    protected function addField(FormBuilderInterface $builder, FilterDefinition $filter, string $defaultType, array $defaultOptions, mixed $data): void
    {
        $options = $defaultOptions + ['required' => false, 'data' => $data];
        if (isset($filter->form['label'])) {
            $options['label'] = $filter->form['label'];
        }
        $builder->add($filter->publicName(), $filter->form['type'] ?? $defaultType, [...$options, ...($filter->form['options'] ?? [])]);
    }
}
