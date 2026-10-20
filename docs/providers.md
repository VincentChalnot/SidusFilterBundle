# Writing a provider

A provider realises the criterion vocabulary on a data source. The core gives you the whole request pipeline; you write
a factory, one compiler per criterion class, and a handler with five small methods. `Sidus\FilterBundle\InMemory` is the
reference implementation: read it first.

## The pieces

| Piece | Contract | Registered as |
|---|---|---|
| Factory | `Provider\QueryHandlerFactoryInterface`: static `getProvider()`, static `configureListOptions()`, static `configureFilterOptions()`, `createQueryHandler(ListDefinition, FilterTypeRegistry, ?CountStrategyInterface)` | autoconfigured, tag `sidus_filter.provider_factory` |
| Criterion compilers | `Provider\CriterionCompilerInterface` (static `getProvider()`, static `getCriterionClass()`) plus your own `compile(...)` signature | autoconfigured, tag `sidus_filter.criterion_compiler` |
| Query handler | extend `Provider\AbstractQueryHandler` | created by the factory, one per request |
| Per-request query | `Provider\QueryContextInterface` | created by the handler, never stored in a service |

The static methods are read **at compile time without instantiating the service**: the provider name, the criterion
classes it supports (its `Capabilities`) and the validation of `options` / `provider_options.<provider>`.

`AbstractQueryHandler::handle()` normalises the input into criteria (collecting violations) and calls you:

```php
protected function createContext(): QueryContextInterface;                          // QueryBuilder wrapper, ES body, array pipeline...
protected function applyCriterion(QueryContextInterface $c, Criterion $criterion): void;
protected function applySort(QueryContextInterface $c, Sort $sort): void;           // append the identifier as tie-breaker
protected function fetch(QueryContextInterface $c, int $offset, int $limit): iterable;
protected function countNative(QueryContextInterface $c): int;                      // count: auto
```

Count strategies (`none`, a service id), lazy totals, `withTotalCount()`, `hasNextPage()`, defaults, locked filters and
violations are handled for you. A handler is single-use; the factory must return a new object on every call.

## Strictness

A provider either realises a criterion with the reference meaning (docs/filter-types.md) or does not register a compiler
for it. There are no approximations: a list whose filters can emit an unsupported criterion fails when the container
compiles, naming the list, the filter and the criterion. A provider-specific approximation (Elasticsearch
`match_phrase_prefix` for "contains") is a provider-specific filter type or criterion, used under `providers:`.
Field-dependent refusals (Doctrine metadata, Elasticsearch field types) belong to your cache warm-up or lint command.

## Checklist

1. Implement the factory and one compiler per supported criterion (`Equals`, `In`, `Contains`, `StartsWith`, `EndsWith`,
   `Range`, `IsNull`, `IsEmpty`, `Matches`, `Not`, `AnyOf`, `AllOf`, `Native`, `Sort`).
2. Honour the semantics: null never matches a comparison; to-many means "any element"; nulls sort first ascending.
3. Run the [contract suite](contract-kit.md) against real storage and declare, then fix or refuse, every divergence.
4. Keep per-request state out of services (the worker-mode rule): the context lives and dies with the handler.
