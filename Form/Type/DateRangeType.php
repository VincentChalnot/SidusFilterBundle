<?php

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form type to allow picking of a date range
 */
class DateRangeType extends AbstractType
{
    public const START_NAME = 'startDate';
    public const END_NAME = 'endDate';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @todo : FIXME : Inject date/time format from configuration and move bootstrap specific elements to different
     *       bundle !!!
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::START_NAME,
                DateType::class,
                [
                    'format' => 'dd/MM/yyyy',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'input-sm form-control',
                        'data-provider' => 'datepicker',
                        'placeholder' => $this->translator->trans('sidus.filter.date_range.start_date'),
                    ],
                ]
            )
            ->add(
                self::END_NAME,
                DateType::class,
                [
                    'format' => 'dd/MM/yyyy',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'input-sm form-control',
                        'data-provider' => 'datepicker',
                        'placeholder' => $this->translator->trans('sidus.filter.date_range.end_date'),
                    ],
                ]
            );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_date_range';
    }
}
