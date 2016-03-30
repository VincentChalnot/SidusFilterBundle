<?php

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::COLUMN_NAME, 'hidden')
            ->add(self::DIRECTION_NAME, 'hidden')
            ->add(self::PAGE_NAME, 'hidden');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->sortConfigClass,
            'pager' => null,
        ]);
    }


    public function getName()
    {
        return 'sidus_sort_config';
    }
}
