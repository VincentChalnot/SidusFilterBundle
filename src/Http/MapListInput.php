<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Http;

use Symfony\Component\HttpKernel\Attribute\ValueResolver;

/**
 * Maps a controller argument of type {@see \Sidus\FilterBundle\Input\ListInput} from the request, for the named list:
 *
 *     public function __invoke(#[MapListInput('posts')] ListInput $input): Response
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapListInput extends ValueResolver
{
    public function __construct(public readonly string $list, bool $disabled = false)
    {
        parent::__construct(ListInputValueResolver::class, $disabled);
    }
}
