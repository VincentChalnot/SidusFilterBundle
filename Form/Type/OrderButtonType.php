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
        /** @var SortConfig $sortConfig */
        $sortConfig = $options['sort_config'];
        if ($sortConfig->getColumn() === $form->getName()) { // @todo maybe use a specific option instead of name ?
            $view->vars['attr']['icon'] = $sortConfig->getDirection() ? 'sort-asc' : 'sort-desc';
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'sort_config',
        ]);
        $resolver->setDefaults([
            'type' => 'submit',
            'button_class' => 'link',
            'label' => false,
            'attr' => [
                'value' => 'ASC',
                'class' => 'pull-right',
                'icon' => 'sort',
            ],
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