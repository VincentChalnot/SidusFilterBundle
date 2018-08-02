<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\FilterBundle\Form\Type;

use Sidus\FilterBundle\DTO\SortConfig;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This widget is used to create sorting buttons
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class OrderButtonType extends SubmitType
{
    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['icon'] = $options['icon'];

        /** @var SortConfig $sortConfig */
        $sortConfig = $options['sort_config'];
        if ($sortConfig->getColumn() === $form->getName()) { // maybe use a specific option instead of name ?
            $view->vars['icon'] = $sortConfig->getDirection() ? 'sort-asc' : 'sort-desc';
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'sort_config',
            ]
        );
        $resolver->setDefaults(
            [
                'type' => SubmitType::class,
                'label' => false,
                'attr' => [
                    // @todo remove any bootstrap specific styles
                    'class' => 'btn btn-xs btn-link pull-right',
                ],
                'icon' => 'sort',
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return SubmitType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_order_button';
    }
}
