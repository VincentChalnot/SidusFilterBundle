<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2020 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;

/**
 * Form type to allow picking of a date range
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DateRangeType extends AbstractType
{
    public const START_NAME = 'startDate';
    public const END_NAME = 'endDate';

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                self::START_NAME,
                DateType::class,
                [
                    'widget' => 'single_text',
                    'attr' => [
                        'placeholder' => 'sidus.filter.date_range.start_date',
                    ],
                ]
            )
            ->add(
                self::END_NAME,
                DateType::class,
                [
                    'widget' => 'single_text',
                    'attr' => [
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
