<?php

namespace Sidus\FilterBundle\Form\Type;

use Sidus\FilterBundle\DTO\SortConfig;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class OrderButtonType
 *
 * @package Sidus\FilterBundle\Form\Type
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
