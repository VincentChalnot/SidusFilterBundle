<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Input;

/**
 * One invalid part of the input. The invalid part falls back to its default; adapters decide whether to reject.
 */
final readonly class Violation
{
    public function __construct(public string $path, public string $message, public mixed $value = null)
    {
    }

    public function withPathPrefix(string $prefix): self
    {
        return new self('' === $this->path ? $prefix : $prefix.'.'.$this->path, $this->message, $this->value);
    }

    /**
     * @return array{path: string, message: string, value: mixed}
     */
    public function toArray(): array
    {
        return ['path' => $this->path, 'message' => $this->message, 'value' => $this->value];
    }
}
