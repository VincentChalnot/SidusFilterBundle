<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

abstract readonly class FieldCriterion implements Criterion
{
    public Field $field;

    /**
     * @param array<string, array<string, mixed>> $providerOptions the emitting filter's `provider_options`, per provider
     *                                                              name: how a provider realises this criterion
     *                                                              (never what it means)
     */
    public function __construct(Field|string $field, public array $providerOptions = [])
    {
        $this->field = Field::of($field);
    }
}
