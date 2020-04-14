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

use Sidus\FilterBundle\DTO\SortConfig;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
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
        $view->vars['arrow'] = '&uarr;&darr;';

        /** @var SortConfig $sortConfig */
        $sortConfig = $options['sort_config'];
        $view->vars['sort_config'] = $sortConfig;
        if ($sortConfig->getColumn() === $form->getName()) { // maybe use a specific option instead of name ?
            $view->vars['arrow'] = $sortConfig->getDirection() ? '&uarr;' : '&darr;';
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
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
