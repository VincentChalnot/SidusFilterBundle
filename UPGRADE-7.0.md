# Upgrading from 6.x to 7.0

7.0 is a **clean break**: no compatibility layer, no deprecation release, no YAML shorthand loader. Every consumer is
expected to migrate in one step. Requirements: PHP 8.4+, Symfony 8.1+.

## Why (worker mode)

In 6.x the `QueryHandlerRegistry` cached the query handlers it created, and a handler held its `QueryBuilder`, its
pager, its form and its filter state. In a long-running worker (FrankenPHP, RoadRunner) the second request got the same
handler back: `LogicException: Pager already applied`, or filters, sort and page of the previous visitor. 7.0 removes the
cause instead of resetting it:

- the container holds only **immutable, request-agnostic** objects (list definitions, filter types, provider factories);
- `QueryHandlerRegistry::createQueryHandler($code)` returns a **new, single-use** handler on every call;
- anything that holds a query, a page or a form is created per request and never stored in a service.

## Packaging

| | |
|---|---|
| `sidus/filter-bundle` 7 | contracts, criteria, filter types, request pipeline, in-memory provider, contract test kit, optional Form / HTTP / Pagerfanta adapters. **No Doctrine dependency.** |
| `sidus/doctrine-filter-bundle` 1 | the Doctrine ORM provider (`provider: doctrine`). Install it next to this bundle. |

## Configuration

| v6 | v7 |
|---|---|
| `sidus_filter.configurations.<code>` | `sidus_filter.lists.<code>` |
| `results_per_page: 15` | `pagination: { per_page: 15 }` (and `max_per_page`) |
| `sortable: [a, b.c]` + `default_sort: {a: desc}` | a `sort` filter: `type: sort`, `options: { columns: { a: a, c: b.c }, default: [{ column: a, direction: desc }] }` |
| `type: text` | `type: text` (operator `contains`) |
| `type: advanced_text` | `type: text` with `options.operators: [contains, starts_with, ...]` |
| `type: advanced_number` | `type: number` with `options.operators: [eq, gt, ...]` |
| `type: choice` / `custom_choice` | `type: choice` |
| `type: entity` | `type: relation` (Doctrine bundle) |
| `type: not_null` | `type: not_null` |
| `options.hidden: true` | `locked: true` (now normalised like any input) |
| `label`, `form_type`, `form_options` | `form: { label, type, options }` |
| `default:` | unchanged, normalised like user input; an invalid default fails at compile time |
| `provider: doctrine` | unchanged, requires `sidus/doctrine-filter-bundle` |
| qk `options.joins`, `totals->regression::int` | Doctrine bundle: named joins; `{ path: totals, json: [regression], cast: integer }` |

Combo option codes (`_like_`, `notlike_`, `greaterthanequals`...) are operator names now (`contains`, `not_starts_with`,
`gte`...); the `sidus.filter.combo.*` translation keys became `sidus.filter.operator.*`
(`sidus.filter.date_range.*`, including `to`, are unchanged).

## Code

| v6 | v7 |
|---|---|
| `QueryHandlerRegistry::getQueryHandler($code)` (cached) | `createQueryHandler($code)` (new each call) |
| `addRawQueryHandlerConfiguration()`, any `add*`/`unset` | removed: definitions come from configuration only (`getDefinition()`, `has()`, `codes()`) |
| `$handler->buildForm()` + `handleRequest($request)` + `getPager()` | `ListFormFactory::create()` / `submit()`, then `$handler->handle(ListInput)` returning a `ListResult` (`items()`, `page()`, `total()`, `violations()`, `state()`) |
| `getQueryBuilder()` / `setQueryBuilder()` | removed; per-request customizers in the Doctrine bundle |
| `ManualCountAdapterInterface`, `setNbResults()` | `count:` strategy (`auto`, `none`, a `CountStrategyInterface` service) and `ListInput::withTotalCount()` |
| `FilterTypeInterface::handleData()` per (provider, type) | filter types emit criteria; compilers per provider realise them |
| invalid input: exceptions or silent `null` | violations in the result; the invalid part falls back to its default |
| `uniqid()` aliases and parameters | deterministic names (Doctrine bundle) |

Removed with no replacement in this package: `DTO\SortConfig`, `Doctrine\*` (moved), `Exception\*`, `Factory\*`
(`FilterFactory`, `QueryHandlerConfigurationFactory`, `QueryHandlerFactoryInterface`), `Filter\Filter(Interface)`,
`Filter\Type\*` (the Doctrine filter types), `Form\Extension\DateExtension`, `Form\Extension\DoctrineEntityExtension`,
`Form\Type\{ComboFilterType, DateRangeType, OrderButtonType, SortConfigType}` (a new `DateRangeType` exists in the form
adapter), `Pagination\*`, `Query\Handler\*` (including `QueryHandlerConfiguration`), the old `Registry\*`, the
Bootstrap 3 and 4 form themes and the v6 documentation.

## Forms and themes

The form is now an adapter: `Sidus\FilterBundle\Form\ListFormFactory`. Themes are `@SidusFilter/form/base.html.twig`,
`bootstrap_6.html.twig` and `bulma_1.html.twig` (Bootstrap 3/4 themes are gone; a Tailwind example lives in
`docs/examples`). The global `DateExtension` and `DoctrineEntityExtension` are removed: string defaults are handled by
the filter types' normalisers. Sorting is a form field with one button per column key (`form.sort.vars.columns[key]`)
instead of `OrderButtonType` and hidden sort fields.

## SidusDataGridBundle

`DataGridRegistry` keeps configurations only and builds a new `DataGrid` per call, with a fresh handler from
`QueryHandlerRegistry::createQueryHandler()`; inline `query_handler` blocks are appended to `sidus_filter.lists` in an
extension `prepend()`. Twig: `datagrid.queryHandler.configuration.sortable` becomes the sort filter's columns,
`datagrid.queryHandler.pager` a pager built with `ListResultAdapter`, `formView.sortable[index]` becomes
`formView.filters.<sort>.vars.columns[key]`. This bundle does not modify the DataGrid bundle.
