<?php

namespace Sidus\FilterBundle\Form\Type;

use Sidus\FilterBundle\DTO\SortConfig;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderButtonType extends SubmitType
{
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'sort_config',
        ]);
        $resolver->setDefaults([
            'type' => 'submit',
            'label' => false,
            'attr' => [
                'class' => 'btn btn-xs btn-link pull-right',
            ],
            'icon' => 'sort',
        ]);
    }

    public function getParent()
    {
        return 'submit';
    }

    public function getName()
    {
        return 'sidus_order_button';
    }
} 