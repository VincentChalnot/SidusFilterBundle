<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/**
 * Unanchored match of a POSIX ERE subset: `.`, `[]`, `*`, `+`, `?`, `{m,n}`, `^`, `$`, `|` and groups; no
 * backreferences, no lookaround.
 */
final readonly class Matches extends FieldCriterion
{
    public function __construct(Field|string $field, public string $pattern, public bool $caseSensitive = true)
    {
        parent::__construct($field);
    }
}
