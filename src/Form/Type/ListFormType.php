<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Type;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Form\FormMapperRegistry;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The GET form of a list: a `filters` child with one field per active, non-locked filter that has a mapper (initial
 * data = the normalised default), and a hidden `page`. Use {@see \Sidus\FilterBundle\Form\ListFormFactory}.
 */
final class ListFormType extends AbstractType
{
    public function __construct(
        private readonly FormMapperRegistry $mappers,
        private readonly FilterTypeRegistry $filterTypes,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ListDefinition $definition */
        $definition = $options['definition'];
        $filters = $builder->create('filters', FormType::class, ['label' => false, 'required' => false]);
        foreach ($definition->filters as $filter) {
            $mapper = $this->mappers->find($filter->type);
            if ($filter->locked || null === $mapper) {
                continue;
            }
            $normalized = $this->filterTypes->get($filter->type)->normalize($filter, $filter->default);
            $data = $normalized->isValid() && !$normalized->isEmpty() ? $mapper->toFormData($filter, $normalized->value) : null;
            $mapper->add($filters, $filter, $data);
        }
        $builder->add($filters);
        $builder->add('page', HiddenType::class, ['required' => false, 'error_bubbling' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('definition');
        $resolver->setAllowedTypes('definition', ListDefinition::class);
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'required' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_list';
    }
}
