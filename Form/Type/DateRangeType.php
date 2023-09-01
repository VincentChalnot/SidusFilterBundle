<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type to allow picking of a date range
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DateRangeType extends AbstractType
{
    public const START_NAME = 'startDate';
    public const END_NAME = 'endDate';

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

        $builder->addModelTransformer(
            new CallbackTransformer(
                static function ($value) {
                    return $value ?? [
                        self::START_NAME => null,
                        self::END_NAME => null,
                    ];
                },
                static function ($value) {
                    return $value ?? [
                        self::START_NAME => null,
                        self::END_NAME => null,
                    ];
                },
            ),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_date_range';
    }
}
