<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;
use Sidus\FilterBundle\InMemory\ValueReader;

/**
 * Contains, StartsWith and EndsWith: literal (no wildcard), Unicode lower-case folded unless case sensitive.
 */
abstract class TextCompiler extends AbstractInMemoryCompiler
{
    abstract protected function test(string $haystack, string $needle): bool;

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var \Sidus\FilterBundle\Criterion\Contains|\Sidus\FilterBundle\Criterion\StartsWith|\Sidus\FilterBundle\Criterion\EndsWith $criterion */
        $reader = $root->reader;
        $needle = $criterion->caseSensitive ? $criterion->text : mb_strtolower($criterion->text);

        return fn (mixed $item): bool => array_any(
            ValueReader::flatten($reader->read($item, $criterion->field)),
            function (mixed $value) use ($criterion, $needle): bool {
                $text = self::asText($value);

                return null !== $text && $this->test($criterion->caseSensitive ? $text : mb_strtolower($text), $needle);
            },
        );
    }

    public static function asText(mixed $value): ?string
    {
        return match (true) {
            \is_string($value) => $value,
            \is_int($value), \is_float($value) => (string) $value,
            $value instanceof \Stringable => (string) $value,
            default => null,
        };
    }
}
