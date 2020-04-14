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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This widget stores the default configuration in the form to apply it again on submission
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SortConfigType extends AbstractType
{
    public const COLUMN_NAME = 'column';
    public const DIRECTION_NAME = 'direction';
    public const PAGE_NAME = 'page';

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
     * @throws AccessException
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
