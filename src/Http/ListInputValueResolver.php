<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Http;

use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver]
final readonly class ListInputValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ListRequestParser $parser,
        private ListDefinitionRegistry $definitions,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if (ListInput::class !== $argument->getType()) {
            return [];
        }
        $attributes = $argument->getAttributes(MapListInput::class, ArgumentMetadata::IS_INSTANCEOF);
        if ([] === $attributes) {
            return [];
        }

        return [$this->parser->parse($request, $this->definitions->get($attributes[0]->list))];
    }
}
