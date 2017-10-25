<?php

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type to allow picking of a date range
 */
class DateRangeType extends AbstractType
{
    const START_NAME = 'startDate';
    const END_NAME = 'endDate';

    /**
     * @todo : FIXME : Inject date/time format from configuration and move bootstrap specific elements to different
     *       bundle !!!
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::START_NAME,
                DateType::class,
                [
                    'format' => 'dd/MM/yyyy',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'input-sm form-control',
                        'data-provider' => 'datepicker',
                        'placeholder' => 'sidus.filter.date_range.start_date',
                    ],
                ]
            )
            ->add(
                self::END_NAME,
                DateType::class,
                [
                    'format' => 'dd/MM/yyyy',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'input-sm form-control',
                        'data-provider' => 'datepicker',
                        'placeholder' => 'sidus.filter.date_range.end_date',
                    ],
                ]
            );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_date_range';
    }
}
