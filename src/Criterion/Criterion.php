<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Criterion;

/**
 * Marker for an immutable, provider-independent statement about a list (section 4 of the design). The vocabulary is
 * closed in the core and extended by provider packages with a new class plus its compilers.
 */
interface Criterion
{
}
