<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Holds the API-style sort value (`-date,title`) in a hidden input and exposes one entry per column key in
 * `form.vars.columns`: `{key, direction: 'asc'|'desc'|null, next: string}` where `next` is the value a button must
 * submit to sort by that column (ascending first, then toggling). Templates and DataGrid header cells render the
 * buttons or links from it: `form.sort.vars.columns[key]`.
 */
final class SortType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $current = [];
        foreach (explode(',', (string) $form->getViewData()) as $token) {
            $token = trim($token);
            if ('' === $token) {
                continue;
            }
            $desc = str_starts_with($token, '-');
            $current[ltrim($token, '-+ ')] = $desc ? 'desc' : 'asc';
        }
        $primary = array_key_first($current);
        $columns = [];
        foreach ($options['columns'] as $key) {
            $direction = $current[$key] ?? null;
            $columns[$key] = [
                'key' => $key,
                'direction' => $direction,
                'primary' => $primary === $key,
                'next' => $primary === $key && 'asc' === $direction ? '-'.$key : $key,
            ];
        }
        $view->vars['columns'] = $columns;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('columns');
        $resolver->setAllowedTypes('columns', 'string[]');
        $resolver->setDefault('error_bubbling', false);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sidus_sort';
    }
}
