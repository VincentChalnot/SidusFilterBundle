<?php

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SortConfigType
 *
 * @package Sidus\FilterBundle\Form\Type
 */
class SortConfigType extends AbstractType
{
    const COLUMN_NAME = 'column';
    const DIRECTION_NAME = 'direction';
    const PAGE_NAME = 'page';

    /** @var string */
    protected $sortConfigClass;

    /**
     * @param string $sortConfigClass
     */
    public function __construct($sortConfigClass)
    {
        $this->sortConfigClass = $sortConfigClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::COLUMN_NAME, HiddenType::class)
            ->add(self::DIRECTION_NAME, HiddenType::class)
            ->add(self::PAGE_NAME, HiddenType::class);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->sortConfigClass,
                'pager' => null,
            ]
        );
    }


    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_sort_config';
    }
}
