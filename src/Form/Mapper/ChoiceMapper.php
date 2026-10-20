<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Mapper;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A ChoiceType when the filter has `choices` (a list, or a map label => value), a text field otherwise.
 */
final class ChoiceMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return 'choice';
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, mixed $data): void
    {
        $choices = $filter->options['choices'];
        if ([] === $choices) {
            $this->addField($builder, $filter, TextType::class, [], null === $data ? null : (string) $data);

            return;
        }
        $multiple = $filter->options['multiple'];
        $this->addField($builder, $filter, ChoiceType::class, [
            'choices' => array_is_list($choices) ? array_combine(array_map('strval', $choices), $choices) : $choices,
            'multiple' => $multiple,
            'placeholder' => $multiple ? false : '',
        ], $data);
    }
}
