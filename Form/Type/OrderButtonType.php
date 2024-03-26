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
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['arrow'] = '&uarr;&darr;';

        /** @var SortConfig $sortConfig */
        $sortConfig = $options['sort_config'];
        $view->vars['sort_config'] = $sortConfig;
        if ($sortConfig->getColumn() === $options['column']) {
            $view->vars['arrow'] = $sortConfig->getDirection() ? '&uarr;' : '&darr;';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'sort_config',
                'column',
            ]
        );
        $resolver->setDefaults(
            [
                'type' => SubmitType::class,
            ]
        );
    }

    public function getParent(): string
    {
        return SubmitType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_order_button';
    }
}
