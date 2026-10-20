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

### Moved or replaced classes (old FQCN → new FQCN)

All in `Sidus\FilterBundle\` unless stated. "Redesigned" means the same name with a different contract: do not just
update the `use` statement.

| v6 | v7 |
|---|---|
| `Factory\QueryHandlerFactoryInterface` | `Provider\QueryHandlerFactoryInterface` (the provider SPI; new signature: static `getProvider()`, static option resolvers, `createQueryHandler(ListDefinition, FilterTypeRegistry, ?CountStrategyInterface)`) |
| `Query\Handler\QueryHandlerInterface` | `Provider\QueryHandlerInterface` (`handle(ListInput): ListResult`, single use) |
| `Query\Handler\AbstractQueryHandler` | `Provider\AbstractQueryHandler` (the request pipeline; providers implement five hooks) |
| `Query\Handler\Configuration\QueryHandlerConfiguration(Interface)` | `Definition\ListDefinition` (readonly) |
| `Filter\Filter`, `Filter\FilterInterface` | `Definition\FilterDefinition` (readonly) |
| `Filter\Type\FilterTypeInterface` | `Filter\FilterTypeInterface` (redesigned: static compile-time half, `normalize()` + `buildCriterion()`) |
| `Filter\Type\AbstractFilterType` | `Filter\Type\AbstractFilterType` (same name, redesigned) |
| `Registry\QueryHandlerRegistry` | `Registry\QueryHandlerRegistry` (same name, redesigned: `createQueryHandler()`), plus `Registry\ListDefinitionRegistry` |
| `Registry\FilterTypeRegistry` | `Registry\FilterTypeRegistry` (same name, indexed by name from a tagged iterator) |
| `Pagination\ManualCountAdapterInterface` | `Provider\CountStrategyInterface` |
| `Form\Type\DateRangeType` | `Form\Type\DateRangeType` (same name, new: `{from, to}` instead of `{startDate, endDate}`) |
| `Form\Type\ComboFilterType` | `Form\Type\OperatorValueType` |
| `Form\Type\OrderButtonType`, `Form\Type\SortConfigType` | `Form\Type\SortType` (one field, per-column state in `vars.columns`) |
| `Exception\MissingQueryHandlerException` | `Exception\UnknownListException` |
| `Exception\MissingQueryHandlerFactoryException` | `Exception\UnknownProviderException` |
| `Exception\MissingFilterException` | `Exception\UnknownFilterTypeException` (and compile-time `Exception\ConfigurationException`) |
| `Doctrine\Factory\DoctrineQueryHandlerFactory` | `Sidus\DoctrineFilterBundle\Factory\DoctrineQueryHandlerFactory` |
| `Query\Handler\Doctrine\DoctrineQueryHandler(Interface)` | `Sidus\DoctrineFilterBundle\Query\DoctrineQueryHandler` |
| `Doctrine\Metadata\DoctrineAttributeMetadataResolver` | `Sidus\DoctrineFilterBundle\Metadata\FieldResolver` |
| `Doctrine\Filter\Type\{Text,AdvancedText,Exact,Choice,CustomChoice,AdvancedNumber,DateRange,NotNull}FilterType` | the provider-independent `Filter\Type\{Text,Exact,Choice,Number,DateRange,NotNull}FilterType` plus the Doctrine bundle's criterion compilers |
| `Doctrine\Filter\Type\EntityFilterType` | `relation` filter type, in the Doctrine bundle |
| `Resources/views/Form/*.twig` | `templates/form/*.html.twig` (`@SidusFilter/form/...`) |

[INFERENCE] The Doctrine bundle's FQCNs above are those of its current sources and may still change before its 1.0.

### Removed, no replacement

`DTO\SortConfig` (sorting is a filter), `DependencyInjection\Configuration` and `SidusFilterExtension` (the bundle
configures itself), `Factory\FilterFactory(Interface)` and `Factory\QueryHandlerConfigurationFactory(Interface)`
(definitions are built once at compile time), `Doctrine\Filter\Type\Abstract{Doctrine,Simple}FilterType`,
`Pagination\DoctrineORMAdapter` (the Doctrine bundle has its own pagination), `Form\Extension\DateExtension`,
`Form\Extension\DoctrineEntityExtension`, `Exception\BadQueryHandlerException`, the Bootstrap 3 and 4 form themes and
the v6 documentation.

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
