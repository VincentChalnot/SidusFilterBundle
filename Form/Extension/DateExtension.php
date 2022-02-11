<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Fixing default DateType to accept strings as defaults
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DateExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                static function ($value) {
                    if (null === $value || $value instanceof \DateTimeInterface) {
                        return $value;
                    }

                    return new \DateTime($value);
                },
                static function ($value) {
                    return $value;
                },
            ),
        );
    }

    public static function getExtendedTypes(): iterable
    {
        return [DateType::class];
    }
}
