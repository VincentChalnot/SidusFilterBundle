<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

/**
 * The per-request, provider-specific query object (a QueryBuilder wrapper, an ES body, an array pipeline...).
 * Created by the query handler for one request, never stored in a service.
 */
interface QueryContextInterface
{
}
