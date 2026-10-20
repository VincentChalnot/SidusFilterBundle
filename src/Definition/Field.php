<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Definition;

/**
 * A logical attribute path of a list definition (`title`, `author.name`), optionally a JSON path inside the value
 * and a cast, resolved per provider. Immutable.
 */
final readonly class Field implements \Stringable
{
    public const array CASTS = ['integer', 'float', 'string', 'boolean', 'date'];

    private const array CAST_ALIASES = ['int' => 'integer', 'bool' => 'boolean'];

    /** @var list<string> */
    public array $json;

    public ?string $cast;

    /**
     * @param list<string> $json
     */
    public function __construct(public string $path, array $json = [], ?string $cast = null)
    {
        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$/', $path)) {
            throw new \InvalidArgumentException(\sprintf('Invalid field path "%s": expected dot-separated identifiers.', $path));
        }
        foreach ($json as $segment) {
            if (!\is_string($segment) || '' === $segment) {
                throw new \InvalidArgumentException(\sprintf('Invalid JSON path for field "%s": segments must be non-empty strings.', $path));
            }
        }
        if (null !== $cast) {
            $cast = self::CAST_ALIASES[$cast] ?? $cast;
            if (!\in_array($cast, self::CASTS, true)) {
                throw new \InvalidArgumentException(\sprintf('Invalid cast "%s" for field "%s", allowed: %s.', $cast, $path, implode(', ', self::CASTS)));
            }
        }
        $this->json = array_values($json);
        $this->cast = $cast;
    }

    public static function of(self|string $field): self
    {
        return $field instanceof self ? $field : self::parse($field);
    }

    /**
     * Parses `path`, `path->json->sub` and `path->json::cast`.
     */
    public static function parse(string $expression): self
    {
        $cast = null;
        if (str_contains($expression, '::')) {
            [$expression, $cast] = explode('::', $expression, 2);
        }
        $segments = explode('->', $expression);
        $path = array_shift($segments);

        return new self($path, $segments, $cast);
    }

    /**
     * @param string|array{path: string, json?: list<string>, cast?: string|null} $config
     */
    public static function fromConfig(string|array $config): self
    {
        if (\is_string($config)) {
            return self::parse($config);
        }
        if (!isset($config['path']) || !\is_string($config['path'])) {
            throw new \InvalidArgumentException('An attribute given as a map requires a "path" key.');
        }
        $unknown = array_diff(array_keys($config), ['path', 'json', 'cast']);
        if ([] !== $unknown) {
            throw new \InvalidArgumentException(\sprintf('Unknown attribute key(s): %s (allowed: path, json, cast).', implode(', ', $unknown)));
        }

        return new self($config['path'], $config['json'] ?? [], $config['cast'] ?? null);
    }

    /**
     * @return list<string>
     */
    public function segments(): array
    {
        return explode('.', $this->path);
    }

    /**
     * @return array{path: string, json: list<string>, cast: string|null}
     */
    public function toArray(): array
    {
        return ['path' => $this->path, 'json' => $this->json, 'cast' => $this->cast];
    }

    /**
     * @param array{path: string, json?: list<string>, cast?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['path'], $data['json'] ?? [], $data['cast'] ?? null);
    }

    public function __toString(): string
    {
        return $this->path.('' === implode('', $this->json) ? '' : '->'.implode('->', $this->json))
            .(null === $this->cast ? '' : '::'.$this->cast);
    }
}
