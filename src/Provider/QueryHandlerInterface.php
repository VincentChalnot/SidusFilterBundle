<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Result\ListResult;

/**
 * Single-use, per-request object: created by the registry for every call, runs one {@see handle()}.
 */
interface QueryHandlerInterface
{
    public function getDefinition(): ListDefinition;

    /**
     * @throws \LogicException when called a second time
     */
    public function handle(ListInput $input): ListResult;
}
