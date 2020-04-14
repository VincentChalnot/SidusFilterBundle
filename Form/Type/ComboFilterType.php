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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Combine a filter input type with a select for parsing options
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ComboFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('input', $options['input_type'], $options['input_options'])
            ->add(
                'option',
                ChoiceType::class,
                [
                    'choices' => $options['options_choices'],
                    'attr' => [
                        'class' => 'btn',
                    ],
                    'widget_form_control_class' => '',
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'input_type',
                'options_choices',
            ]
        );
        $resolver->setAllowedTypes('input_type', ['string']);
        $resolver->setAllowedTypes('options_choices', ['array']);
        $resolver->setDefaults(
            [
                'input_options' => [],
            ]
        );
        $resolver->setAllowedTypes('input_options', ['array']);
    }

    /**
     * @return string|null
     */
    public function getBlockPrefix()
    {
        return 'sidus_combo_filter';
    }
}
