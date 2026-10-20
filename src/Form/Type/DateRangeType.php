<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * `from` and `to` date fields (HTML5 date inputs). View data: `{from, to}` as `Y-m-d` strings.
 */
final class DateRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $field = [
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
            'required' => false,
            'label' => false,
        ];
        $builder
            ->add('from', DateType::class, $field + ['attr' => ['placeholder' => 'sidus.filter.date_range.start_date']])
            ->add('to', DateType::class, $field + ['attr' => ['placeholder' => 'sidus.filter.date_range.end_date']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['timezone' => 'UTC', 'compound' => true, 'error_bubbling' => false]);
        $resolver->setAllowedTypes('timezone', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_date_range';
    }
}
