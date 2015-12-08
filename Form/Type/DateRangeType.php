<?php

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class DateRangeType extends AbstractType
{
    const START_NAME = 'startDate';
    const END_NAME = 'endDate';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::START_NAME, 'date', [
                'format' => 'dd/MM/yyyy',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'input-sm form-control',
                    'data-datepicker' => '',
                    'placeholder' => 'Date de début',
                ],
            ])
            ->add(self::END_NAME, 'date', [
                'format' => 'dd/MM/yyyy',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'input-sm form-control',
                    'data-datepicker' => '',
                    'placeholder' => 'Date de fin',
                ],
            ]);
    }

    public function getName()
    {
        return 'sidus_date_range';
    }
} 