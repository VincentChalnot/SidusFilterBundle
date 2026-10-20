<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Http;

use Sidus\FilterBundle\Result\ListResult;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Strict by default: a result with violations is a `400` `application/problem+json` (RFC 9457 shape) listing the
 * violations (path and message, never the submitted value). Non-strict answers `200` with the defaults applied and
 * the violations next to the meta.
 */
final readonly class ListResponseFactory
{
    public function __construct(
        private ListResultNormalizer $normalizer = new ListResultNormalizer(),
        private bool $strict = true,
    ) {
    }

    /**
     * @param (callable(mixed): mixed)|null $itemNormalizer
     */
    public function create(ListResult $result, ?callable $itemNormalizer = null, ?bool $strict = null): JsonResponse
    {
        $violations = array_map(
            static fn (array $violation): array => ['path' => $violation['path'], 'message' => $violation['message']],
            $result->violations()->toArray(),
        );
        if ([] !== $violations && ($strict ?? $this->strict)) {
            return new JsonResponse(
                ['type' => 'about:blank', 'title' => 'Invalid list parameters', 'status' => 400, 'violations' => $violations],
                400,
                ['Content-Type' => 'application/problem+json'],
            );
        }
        $body = $this->normalizer->normalize($result, $itemNormalizer);
        if ([] !== $violations) {
            $body['violations'] = $violations;
        }

        return new JsonResponse($body);
    }
}
