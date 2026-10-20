<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An operator choice next to a value field. View data: `{operator, value}`, the raw input of operator filters.
 */
final class OperatorValueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $operators = $options['operators'];
        $builder
            ->add('operator', ChoiceType::class, [
                'choices' => array_combine($operators, $operators),
                'choice_label' => static fn (string $operator): string => 'sidus.filter.operator.'.$operator,
                'choice_translation_domain' => 'messages',
                'placeholder' => false,
                'required' => true,
                'data' => $operators[0],
                'label' => false,
            ])
            ->add('value', $options['value_type'], ['required' => false, 'label' => false, ...$options['value_options']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('operators');
        $resolver->setAllowedTypes('operators', 'string[]');
        $resolver->setDefaults(['value_type' => TextType::class, 'value_options' => [], 'compound' => true, 'error_bubbling' => false]);
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_operator_value';
    }
}
