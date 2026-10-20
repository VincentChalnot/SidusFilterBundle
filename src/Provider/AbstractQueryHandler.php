<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Provider;

use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Input\Violation;
use Sidus\FilterBundle\Input\ViolationList;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Result\ListResult;

/**
 * The request pipeline shared by every provider: normalise the input into criteria (collecting violations, never
 * throwing on user input), hand them to the provider, fetch one page, expose the total lazily. Single use.
 *
 * Input rules: a filter absent (or null) in the input takes its configured default; an explicitly blank value
 * ('' or []) clears it; a locked filter always takes its default and ignores the input. An invalid part falls back
 * to the filter's default (inactive without one) and is reported as a violation.
 */
abstract class AbstractQueryHandler implements QueryHandlerInterface
{
    private bool $used = false;

    public function __construct(
        protected readonly ListDefinition $definition,
        protected readonly FilterTypeRegistry $filterTypes,
        protected readonly ?CountStrategyInterface $countStrategy = null,
    ) {
    }

    public function getDefinition(): ListDefinition
    {
        return $this->definition;
    }

    final public function handle(ListInput $input): ListResult
    {
        if ($this->used) {
            throw new \LogicException(\sprintf('The query handler of list "%s" is single-use: create a new one with QueryHandlerRegistry::createQueryHandler().', $this->definition->code));
        }
        $this->used = true;

        $violations = [];
        [$page, $perPage] = $this->normalizePaging($input, $violations);
        [$criteria, $sort, $filterState] = $this->normalizeFilters($input, $violations);

        $context = $this->createContext();
        foreach ($criteria as $criterion) {
            $this->applyCriterion($context, $criterion);
        }
        if (null !== $sort) {
            $this->applySort($context, $sort);
        }

        $items = array_values([...$this->fetch($context, ($page - 1) * $perPage, $perPage)]);

        return new ListResult(
            $items,
            $page,
            $perPage,
            $this->totalSource($context, $input),
            new ViolationList(...$violations),
            ['filters' => $filterState, 'page' => $page, 'per_page' => $perPage],
        );
    }

    /** Creates the per-request native query object. */
    abstract protected function createContext(): QueryContextInterface;

    abstract protected function applyCriterion(QueryContextInterface $context, Criterion $criterion): void;

    /** The provider appends the identifier as final tie-breaker. */
    abstract protected function applySort(QueryContextInterface $context, Sort $sort): void;

    /** @return iterable<mixed> at most `$limit` items starting at `$offset` */
    abstract protected function fetch(QueryContextInterface $context, int $offset, int $limit): iterable;

    /** The provider's native total (count strategy `auto`). */
    abstract protected function countNative(QueryContextInterface $context): int;

    /**
     * @return int|\Closure(): ?int|null
     */
    private function totalSource(QueryContextInterface $context, ListInput $input): int|\Closure|null
    {
        if (null !== $input->totalCount) {
            return $input->totalCount;
        }

        return fn (): int => $this->countNative($context);
    }

    /**
     * @param list<Violation> $violations
     *
     * @return array{0: int, 1: int}
     */
    private function normalizePaging(ListInput $input, array &$violations): array
    {
        $perPage = $this->definition->perPage;
        if (null !== $input->perPage && '' !== $input->perPage) {
            $value = self::toPositiveInt($input->perPage);
            if (null === $value || $value > $this->definition->maxPerPage) {
                $violations[] = new Violation('per_page', \sprintf('Expected an integer between 1 and %d.', $this->definition->maxPerPage), $input->perPage);
            } else {
                $perPage = $value;
            }
        }
        $page = 1;
        if (null !== $input->page && '' !== $input->page) {
            $value = self::toPositiveInt($input->page);
            if (null === $value || $value > intdiv(\PHP_INT_MAX, $perPage + 1)) {
                $violations[] = new Violation('page', 'Expected a positive integer.', $input->page);
            } else {
                $page = $value;
            }
        }

        return [$page, $perPage];
    }

    private static function toPositiveInt(mixed $value): ?int
    {
        if (\is_string($value) && 1 === preg_match('/^[0-9]{1,18}$/', $value)) {
            $value = (int) $value;
        }

        return \is_int($value) && $value >= 1 ? $value : null;
    }

    /**
     * @param list<Violation> $violations
     *
     * @return array{0: list<Criterion>, 1: Sort|null, 2: array<string, mixed>}
     */
    private function normalizeFilters(ListInput $input, array &$violations): array
    {
        $criteria = [];
        $sort = null;
        $state = [];
        foreach ($this->definition->filters as $filter) {
            $name = $filter->publicName();
            $raw = $filter->default;
            if (!$filter->locked && null !== ($input->filters[$name] ?? null)) {
                $raw = $input->filters[$name];
            }
            $type = $this->filterTypes->get($filter->type);
            $normalized = $type->normalize($filter, $raw);
            if (!$normalized->isValid()) {
                foreach ($normalized->violations as $violation) {
                    $violations[] = $violation->withPathPrefix('filters.'.$name);
                }
                $normalized = $type->normalize($filter, $filter->default);
                if (!$normalized->isValid()) {
                    continue;
                }
            }
            if ($normalized->isEmpty()) {
                continue;
            }
            $criterion = $type->buildCriterion($filter, $normalized->value);
            if (null === $criterion) {
                continue;
            }
            $state[$name] = $normalized->value;
            if ($criterion instanceof Sort) {
                if (null !== $sort) {
                    throw new \LogicException(\sprintf('List "%s" has several active sort filters.', $this->definition->code));
                }
                $sort = $criterion;
                continue;
            }
            $criteria[] = $criterion;
        }

        return [$criteria, $sort, $state];
    }
}
