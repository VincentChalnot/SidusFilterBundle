<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory\Compiler;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Matches;
use Sidus\FilterBundle\Criterion\PosixRegex;
use Sidus\FilterBundle\InMemory\ValueReader;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;

final class MatchesCompiler extends AbstractInMemoryCompiler
{
    public static function getCriterionClass(): string
    {
        return Matches::class;
    }

    public function compile(Criterion $criterion, InMemoryCompiler $root): \Closure
    {
        /** @var Matches $criterion */
        $reader = $root->reader;

        return static function (mixed $item) use ($reader, $criterion): bool {
            return array_any(
                ValueReader::flatten($reader->read($item, $criterion->field)),
                static function (mixed $value) use ($criterion): bool {
                    $text = TextCompiler::asText($value);

                    return null !== $text && PosixRegex::matches($criterion->pattern, $criterion->caseSensitive, $text);
                },
            );
        };
    }
}
