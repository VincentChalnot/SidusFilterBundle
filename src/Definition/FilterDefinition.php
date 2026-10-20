<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Definition;

/**
 * One configured filter of a list. `$name` is the public input name (unique among the active filters of the list),
 * `$key` the configuration key. Immutable; `$options` are already resolved by the filter type.
 */
final readonly class FilterDefinition
{
    /** @var list<Field> */
    public array $attributes;

    /**
     * @param list<Field|string>                $attributes
     * @param list<string>                      $providers       empty = portable, active for every provider
     * @param array<string, mixed>              $options
     * @param array<string, array<string,mixed>> $providerOptions per provider name
     * @param array<string, mixed>              $form            read by the form adapter only
     */
    public function __construct(
        public string $key,
        public string $type,
        array $attributes = [],
        public ?string $name = null,
        public array $providers = [],
        public array $options = [],
        public array $providerOptions = [],
        public bool $locked = false,
        public mixed $default = null,
        public array $form = [],
    ) {
        $fields = array_map(Field::of(...), $attributes);
        $this->attributes = [] === $fields ? [Field::of($key)] : array_values($fields);
    }

    /** The public input name. */
    public function publicName(): string
    {
        return $this->name ?? $this->key;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProviderOptions(string $provider): array
    {
        return $this->providerOptions[$provider] ?? [];
    }

    public function isActiveFor(string $provider): bool
    {
        return [] === $this->providers || \in_array($provider, $this->providers, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type,
            'attributes' => array_map(static fn (Field $field): array => $field->toArray(), $this->attributes),
            'providers' => $this->providers,
            'options' => $this->options,
            'provider_options' => $this->providerOptions,
            'locked' => $this->locked,
            'default' => $this->default,
            'form' => $this->form,
        ];
    }

    /**
     * @param array<string, mixed> $data the shape produced by {@see toArray()}
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            type: $data['type'],
            attributes: array_map(Field::fromArray(...), $data['attributes']),
            name: $data['name'] ?? null,
            providers: $data['providers'] ?? [],
            options: $data['options'] ?? [],
            providerOptions: $data['provider_options'] ?? [],
            locked: $data['locked'] ?? false,
            default: $data['default'] ?? null,
            form: $data['form'] ?? [],
        );
    }
}
