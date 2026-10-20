<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Form;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Maps one filter type to a form field, independently of the provider. Registered per filter type name (tag
 * `sidus_filter.form_mapper`, autoconfigured). A filter whose type has no mapper has no form field (API only).
 *
 * The form never applies anything to a query: submitted view data is converted back to *raw input* by
 * {@see toRaw()} and goes through the same normaliser as an API call.
 */
interface FormFilterMapperInterface
{
    /** The name of the filter type this mapper handles. */
    public function getFilterType(): string;

    /**
     * Adds the field, named after the filter's public name, to the `filters` builder.
     *
     * @param ListDefinition $list the list the filter belongs to (e.g. to derive a related class from the provider's options)
     * @param mixed          $data the initial form data ({@see toFormData()} of the normalised default), null when none
     */
    public function add(FormBuilderInterface $builder, FilterDefinition $filter, ListDefinition $list, mixed $data): void;

    /** Submitted view data of the field to raw input. */
    public function toRaw(FilterDefinition $filter, mixed $viewData): mixed;

    /** A normalised value (what the filter type's `normalize()` returned) to the field's model data. */
    public function toFormData(FilterDefinition $filter, mixed $normalized): mixed;
}
