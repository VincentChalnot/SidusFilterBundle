<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Form\Type\ListFormType;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Input\Violation;
use Sidus\FilterBundle\Result\ListResult;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Form adapter (design 12.1). The form is a view of the input, never of the query:
 *
 *     $form = $factory->create($definition);
 *     $input = $factory->submit($form, $request->query->all());   // ListInput from the submitted view data
 *     $result = $handler->handle($input);
 *     $factory->addViolations($form, $result);                    // violations become form errors
 *
 * One form per request: create a new one every time.
 */
final readonly class ListFormFactory
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private FormMapperRegistry $mappers,
    ) {
    }

    /**
     * @param array<string, mixed> $options extra ListFormType options
     */
    public function create(ListDefinition $definition, array $options = []): FormInterface
    {
        return $this->formFactory->createNamed('', ListFormType::class, null, ['definition' => $definition] + $options);
    }

    /**
     * Submits the query (a top-level `sort` is read as the sort filter) and returns the raw input: what the
     * query carries for a filter is passed on untouched (so an invalid value reaches the normaliser and becomes a
     * violation), what it does not carry is the field's initial data (the default) converted by the mapper's
     * `toRaw()`. Filters without a form field, and locked ones, are not part of the input.
     *
     * @param array<string, mixed> $query
     */
    public function submit(FormInterface $form, array $query): ListInput
    {
        $definition = $this->definitionOf($form);
        $input = ListInput::fromArray($query, $definition->findSortFilter()?->publicName() ?? 'sort');
        $form->submit(['filters' => $input->filters, 'page' => $input->page], false);

        $filters = [];
        foreach ($definition->filters as $filter) {
            $mapper = $this->mappers->find($filter->type);
            if ($filter->locked || null === $mapper || !$form->get('filters')->has($filter->publicName())) {
                continue;
            }
            $name = $filter->publicName();
            $filters[$name] = \array_key_exists($name, $input->filters)
                ? $input->filters[$name]
                : $mapper->toRaw($filter, $form->get('filters')->get($name)->getViewData());
        }

        return new ListInput($filters, '' === ($page = $form->get('page')->getViewData()) ? null : $page, $input->perPage);
    }

    /**
     * Adds every violation of the result as an error of the closest existing field (`filters.<name>.<sub>`), or of
     * the form itself for `page` and `per_page`.
     */
    public function addViolations(FormInterface $form, ListResult $result): void
    {
        foreach ($result->violations() as $violation) {
            $this->target($form, $violation)->addError(new FormError($violation->message, $violation->message));
        }
    }

    private function target(FormInterface $form, Violation $violation): FormInterface
    {
        $segments = explode('.', $violation->path);
        if ('filters' !== $segments[0]) {
            return 'page' === $segments[0] && $form->has('page') ? $form->get('page') : $form;
        }
        $target = $form;
        foreach ($segments as $segment) {
            if (!$target->has($segment)) {
                break;
            }
            $target = $target->get($segment);
        }

        return $target;
    }

    private function definitionOf(FormInterface $form): ListDefinition
    {
        return $form->getConfig()->getOption('definition');
    }
}
