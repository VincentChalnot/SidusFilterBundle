<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

use Sidus\FilterBundle\Definition\Field;

/** Prefix match. Wildcards in the text are literal; case folding is Unicode lower-case, accents are not folded. */
final readonly class StartsWith extends FieldCriterion
{
    public function __construct(Field|string $field, public string $text, public bool $caseSensitive = false)
    {
        parent::__construct($field);
    }
}
