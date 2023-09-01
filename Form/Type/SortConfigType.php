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

    protected string $sortConfigClass;

    public function __construct(string $sortConfigClass)
    {
        $this->sortConfigClass = $sortConfigClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::COLUMN_NAME, HiddenType::class)
            ->add(self::DIRECTION_NAME, HiddenType::class)
            ->add(self::PAGE_NAME, HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->sortConfigClass,
                'pager' => null,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_sort_config';
    }
}
