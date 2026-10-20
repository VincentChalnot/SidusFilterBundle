# Migrating the consumers of sidus/filter-bundle 6.x

File-by-file migration notes for the code that consumes the v6 bundle. **Nothing in those repositories was edited**: these are
notes to apply by hand, in the order given in [Order of work](#order-of-work).

| Section | Consumer | Becomes |
|---|---|---|
| [(a)](#a-sidusdatagridbundle-50) | `SidusDataGridBundle` (`/home/vincent/www/sidus-bundles/SidusDataGridBundle`) | 5.0, requires `sidus/filter-bundle ^7.0` |
| [(b)](#b-comtso) | ComTSo (`/home/vincent/www/comtso`) | bumps both bundles, deletes its worker-mode workarounds |
| [(c)](#c-eav-filter-bundle-30) | EAV filter bundle (clone of `VincentChalnot/SidusEAVFilterBundle`) | `sidus/eav-filter-bundle` 3.0, a Doctrine dialect |
| [(d)](#d-keres-platform-and-qk-sds-api) | keres-platform and qk-sds-api | delete custom types/forks, named joins and JSON paths |

## How to read this

- Every claim about existing code carries a `path:line` read at the time of writing; anything not read directly is marked
  `[INFERENCE]`, a proposal (not derived from code) is marked `[PROPOSAL]` or `[P]`. Paths are relative to the repository the
  section is about unless prefixed (`FilterBundle:` = this repository, `dfb:` = `SidusDoctrineFilterBundle`).
- Line numbers of **`sidus/doctrine-filter-bundle` may have drifted**: it is being built in parallel (named joins, JSON paths,
  MariaDB/MySQL support and the `relation` filter type were added or are being added while these notes were written). Re-check
  its references before relying on them.
- Dependencies assumed: the Doctrine bundle provides `type: relation` and MariaDB/MySQL support (requirements, not open
  questions). The sections say so where they depend on it.

## Order of work

1. `sidus/filter-bundle` 7.0 and `sidus/doctrine-filter-bundle` 1.0 released (named joins, JSON paths, expression sort,
   `relation`, MariaDB/MySQL: see the gaps below).
2. `SidusDataGridBundle` 5.0 (section a): registry, `DataGrid`, extension `prepend()`, Twig, form type extension.
3. `sidus/admin-bundle`: ComTSo pins `sidus/datagrid-bundle ~4.0` through it (`vendor/sidus/admin-bundle/composer.json:34`, per
   section b), so a datagrid-5 compatible release of the admin bundle is a prerequisite for ComTSo (not covered here).
4. Applications: ComTSo (b), keres-platform and qk-sds-api (d).
5. EAV filter bundle 3.0 (c), once the Doctrine bundle has the extension points listed in section (c).

## Findings that cross sections

- **Form field layout.** In 7.0 the sort field is `form.filters.<sortFilterName>`, not a `form.sort` child: DataGrid templates
  and `get_filter_columns` must skip children whose block prefix is `sidus_sort` (section a, 2.5 and 3.1). The submitted form is
  named `''`: URLs become `?filters[title]=x&sort=-date&page=2` instead of `?form[filters][...]` (section b lists the tests that
  encode the old shape; **`tests/Admin/WorkerModeTest.php` keeps passing silently while guarding nothing** if its filtered URL is
  not migrated).
- **Sort keys** must match `^[A-Za-z0-9_]+$` (`FilterBundle:src/Filter/Type/SortFilterType.php`): dotted v6 `sort_column` paths
  become keys (`forum` => `forum.title`).
- **Boolean choices.** `choice` matches by `(string)`: a YAML `false` choice cannot round-trip; quote `'1'`/`'0'` (section b).
- **Dates.** `date_range` defaults to UTC: set `options.timezone` where v6 used the PHP default timezone (section b).
- **Single-use handlers** end every pattern where a long-lived handler was reached into (ComTSo and keres reflection-based
  decorators, qk's per-file `ListActions`): one `createQueryHandler()` per use.
- **Doctrine bundle gaps seen by these notes** (to be settled by its author): named joins and JSON paths in `FieldCapabilities`,
  `provider_options.doctrine.expression` for computed sorts, refusal of `Equals`/`In` on a path ending at an association (attributes
  must end on `.id`), refusal of `Equals`/`In` on JSON columns (ComTSo `admin_role`), and the missing EAV extension points
  (M1 to M8 in section c).
- **D1 resolved: EAV provider (`sidus_eav`)**: A separate bundle, built on top of the Doctrine provider after 1.0. Not part of this 1.0 release. The planned extension points (M1–M8) are referenced in section (c) for future work only.
- Core decisions the notes still need from the owner: a `ListFormType` extension hook for DataGrid's reset/submit/actions children (section a proposes a type extension), and how `InMemorySourceInterface::items()` can receive per-request context (qk `ReadAction`, open question O5 of section d).

---

## (a) SidusDataGridBundle 5.0

Scope: `/home/vincent/www/sidus-bundles/SidusDataGridBundle` (HEAD `18d9b46`), moved to **5.0** on top of `sidus/filter-bundle ^7.0`.
All `path:line` below are relative to that repo unless prefixed by `FilterBundle:` (= `/home/vincent/www/sidus-bundles/SidusFilterBundle`, branch v7.x). Lines I did not read are marked `[INFERENCE]`.
Read-only analysis: nothing was edited.

Summary of the shape change: v6 handed the DataGrid a **long-lived, stateful** `QueryHandlerInterface` (form + request handling + pager in one object, cached in `DataGridRegistry::$dataGrids`). v7 handlers are **single-use** and `ListResult` is immutable, so a `DataGrid` instance becomes a **per-request object** built by the registry on every `getDataGrid()` call. Configurations stay in the registry (shared state), nothing request-specific is cached.

---

### 1. composer.json

| Before | After |
|---|---|
| `"sidus/filter-bundle": ">=4.0"` (`composer.json:35`, the last line of `require`) | `"sidus/filter-bundle": "^7.0"` |

Other constraints (`composer.json:27-35`): `php >=8.1`; `symfony/{config,dependency-injection,form,http-foundation,options-resolver,property-access,routing,translation} >=6.3`; `twig/twig >=3.0`.

Implications (FilterBundle's own requirements, `FilterBundle:composer.json:36-58`):
- FilterBundle 7 requires `php >=8.4` and `symfony/{config,dependency-injection,http-kernel,options-resolver,property-access} ^8.1`. Composer will therefore refuse the DataGrid's `>=8.1`/`>=6.3` floors only in the sense that the *resolved* set is PHP 8.4 / Symfony 8.1; the DataGrid constraints themselves stay satisfiable. Recommended: raise `php` to `>=8.4` and the Symfony constraints to `^8.1` so the DataGrid's declared floor is what is actually tested (otherwise `--prefer-lowest` is a lie). [INFERENCE: the owner may prefer to keep wide ranges; they do not conflict.]
- New runtime requirements of the DataGrid 5.0 code (the form adapter, the Pagerfanta bridge and the container prepend are optional in FilterBundle, `FilterBundle:composer.json:66-72` "suggest"/"require-dev"): add `"pagerfanta/core": "^4.0"` (the `ListResultAdapter` and the templates use `Pagerfanta`), keep `symfony/form` and `symfony/translation`, add `symfony/twig-bridge` (form themes) and `symfony/framework-bundle` if you rely on the form-adapter autoconfiguration (`sidus_filter.form_adapter` tag, `FilterBundle:src/DependencyInjection/OptionalServicesPass.php:16`) [INFERENCE on the last one].
- `symfony/http-foundation` and `symfony/routing` stay (used by `Twig/RendererExtension.php`, `page_path`).
- Branch alias / tag: this is a BC break → `5.0.0`; add a `CHANGELOG.md` entry (file exists at repo root).

---

### 2. Every place that touches the filter bundle

Inventory (grep of `filter|queryhandler|query_handler|pager` over php/twig/yaml): `DependencyInjection/Configuration.php`, `DependencyInjection/SidusDataGridExtension.php`, `Model/DataGrid.php`, `Registry/DataGridRegistry.php`, `Twig/RendererExtension.php`, `Resources/views/{DataGrid,Form,Pager,partials}/*`, `composer.json`. Things I checked and found **absent**: no compiler pass in `DependencyInjection/` (only `Configuration.php` and `SidusDataGridExtension.php`), no `Renderer/*` class referencing the filter bundle (`Renderer/` only has the column value/label renderers; its only `sidus.datagrid.*` use is `Renderer/DefaultColumnValueRenderer.php:116-117`), and `SidusDataGridBundle.php` is an empty `Bundle` subclass (no `build()` override).

#### 2.1 `Registry/DataGridRegistry.php`

v6 (`Registry/DataGridRegistry.php`):
- `:15-19` imports `MissingFilterException`, `MissingQueryHandlerException`, `MissingQueryHandlerFactoryException` (unused in the file) and `Sidus\FilterBundle\Registry\QueryHandlerRegistry`. These exception classes do not exist in v7 (see UPGRADE-7.0.md mapping) → delete the imports.
- `:29-33` `protected array $dataGrids = []` (cache of built `DataGrid`) + `$dataGridConfigurations`.
- `:35-38` constructor injects `QueryHandlerRegistry $queryHandlerRegistry`.
- `:45-48` `addDataGrid(DataGrid)`, `:50-57` `getDataGrid()` returns the **cached** instance, `:59-62` `hasDataGrid()`.
- `:64-82` `buildDataGrid()`:
  ```php
  $this->queryHandlerRegistry->addRawQueryHandlerConfiguration('__sidus_datagrid.'.$code, $configuration['query_handler']);   // :71-74
  $configuration['query_handler'] = $this->queryHandlerRegistry->getQueryHandler('__sidus_datagrid.'.$code);            // :75
  $dataGrid = new DataGrid($code, $configuration);                                                                       // :77
  $this->addDataGrid($dataGrid); unset($this->dataGridConfigurations[$code]);                                             // :78-79
  ```
  `addRawQueryHandlerConfiguration()` / `getQueryHandler()` do not exist in v7 (`FilterBundle:src/Registry/QueryHandlerRegistry.php:35-68` exposes only `has()`, `getDefinition()`, `codes()`, `createQueryHandler()`; "No `add*` methods", `FilterBundle:docs/DESIGN.md:494`).

v7 replacement (full file):

```php
namespace Sidus\DataGridBundle\Registry;

use Sidus\DataGridBundle\Model\DataGrid;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use UnexpectedValueException;

class DataGridRegistry
{
    /** @var array<string, array> configurations only, never request state */
    protected array $dataGridConfigurations = [];

    public function __construct(
        protected QueryHandlerRegistry $queryHandlerRegistry,
        protected ListFormFactory $listFormFactory,
    ) {
    }

    public function addRawDataGridConfiguration(string $code, array $configuration): void
    {
        $this->dataGridConfigurations[$code] = $configuration;
    }

    public function hasDataGrid(string $code): bool
    {
        return array_key_exists($code, $this->dataGridConfigurations);
    }

    /** A NEW single-use DataGrid (and a NEW query handler) on every call. */
    public function getDataGrid(string $code): DataGrid
    {
        $configuration = $this->dataGridConfigurations[$code]
            ?? throw new UnexpectedValueException("No data-grid with code : {$code}");
        $listCode = $configuration['list'];            // see 2.3: '__sidus_datagrid.<code>' or an existing list code
        unset($configuration['list']);

        return new DataGrid(
            $code,
            $configuration,
            $this->queryHandlerRegistry->getDefinition($listCode),
            $this->queryHandlerRegistry->createQueryHandler($listCode),
            $this->listFormFactory,
        );
    }
}
```

Changes to call out:
- **Delete** `addDataGrid()` and the `$dataGrids` cache (`:29-30`, `:45-48`, `:52-56`, `:78-79`). Anyone calling `addDataGrid()` externally [INFERENCE: nothing in this repo calls it besides `:78`] must migrate; the `sidus.datagrid` autoconfigure tag on `DataGrid` (`Model/DataGrid.php:33`, `#[AutoconfigureTag('sidus.datagrid')]`) has no consumer in this repo (grep: only that line) → remove the attribute, a `DataGrid` is no longer a service.
- `hasDataGrid()` no longer checks the cache (`:61`).
- `ListFormFactory` is only registered when the form adapter is available (`FilterBundle:src/DependencyInjection/OptionalServicesPass.php`, tag `sidus_filter.form_adapter`) [INFERENCE on conditional registration]; the DataGrid hard-requires `symfony/form` anyway (`composer.json:30`).
- The container auto-wires the registry: `Resources/config/services.yaml:7-8` loads `Sidus\DataGridBundle\:` from `../../` excluding `{DependencyInjection,Documentation,Model}`; `DataGridRegistry` is picked up by autowiring and `SidusDataGridExtension::load()` fetches it via `$container->getDefinition(DataGridRegistry::class)` (`DependencyInjection/SidusDataGridExtension.php:48`). Nothing to change in `services.yaml` (the extra constructor arg autowires).
- Worker-mode (FrankenPHP/RoadRunner) consequence: a `DataGrid` obtained once in a long-lived service is stale after one request; controllers must call `getDataGrid()` per request (they already do in the documented flow, `Documentation/03-usage.md`).

#### 2.2 `Model/DataGrid.php`

| v6 (`Model/DataGrid.php`) | v7 |
|---|---|
| `:17` `use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;` | `use Sidus\FilterBundle\Provider\QueryHandlerInterface;` (`FilterBundle:src/Provider/QueryHandlerInterface.php`, methods `getDefinition()`, `handle(ListInput): ListResult`, single-use) |
| `:38`, `:74-82` `protected ?QueryHandlerInterface $queryHandler`, `getQueryHandler()`, `setQueryHandler()` | keep **both** `getQueryHandler()` and `setQueryHandler()` (public): the Doctrine handler's `withCustomizer()` returns a clone and is not on the interface, so callers must swap the clone in (see below). The handler is constructor-injected; PropertyAccess no longer sets it from config because `query_handler` is removed from the configuration array (2.1) |
| `:57-72` constructor `__construct(string $code, array $configuration)` + `PropertyAccess::setValue($this, $key, $option)` for every key (`:65-67`) | add `ListDefinition $definition, QueryHandlerInterface $queryHandler, ListFormFactory $listFormFactory` params; the config array no longer contains `query_handler`/`list` |
| `:188-195` `buildForm(FormBuilderInterface $builder): FormInterface` → `$this->getQueryHandler()->buildForm($builder)` | replaced by `createForm()` (below). `ListFormFactory::create(ListDefinition $definition, array $options = [])` takes **no builder** (`FilterBundle:src/Form/ListFormFactory.php:34-37`): the form is always created by the factory, named `''`, GET, no CSRF. Users who passed a custom builder (`Documentation/03-usage.md:19-22`) pass `$options` instead (`form_options` config key still exists: `DependencyInjection/Configuration.php:96`) |
| `:198-201` `handleRequest(Request $request)` → `$this->queryHandler->handleRequest($request)` | `submit` + `handle` + violations (below) |
| `:203-206` `handleArray(array $data)` | same body with `$data` instead of `$request->query->all()` |
| `:208-211` `getPager(): iterable` → `$this->getQueryHandler()->getPager()` | `ListResultAdapter::createPager($this->result)` |
| `:179-186` `getFormView()` (memoised `createView()`) | unchanged |
| `:250-259` `buildFilterActions()` counts visible filters with `$this->getQueryHandler()->getConfiguration()->getFilters()` and `$filter->getOption('hidden')` (`:254`) | `$this->definition->filters` (`FilterDefinition`s); `hidden` no longer exists: use `!$filter->locked` (locked filters have no form field, `FilterBundle:src/Form/Type/ListFormType.php:36-38`) and "has a form field", i.e. `$this->form->get('filters')->count() > 0` |
| `:262-275` / `:276-289` `buildResetAction()`/`buildSubmitAction()` add `filterResetButton` / `filterSubmitButton` to the **builder**, and `:291-306` `buildDataGridActions()` adds an `actions` child | The form is created by `ListFormFactory`; children can no longer be added to a builder the DataGrid owns. See "Extra form children" below |

New `DataGrid` runtime (sketch, the 4 public methods used by controllers and Twig keep their names):

```php
private ?FormInterface $form = null;
private ?ListResult $result = null;
private ?Pagerfanta $pager = null;

public function __construct(string $code, array $configuration, private ListDefinition $definition,
    private QueryHandlerInterface $queryHandler, private ListFormFactory $listFormFactory) { /* columns as before */ }

public function getDefinition(): ListDefinition { return $this->definition; }

/** Name of the sort filter as published in the form/query, null when the list is not sortable. */
public function getSortFilterName(): ?string { return $this->definition->findSortFilter()?->publicName(); }  // ListDefinition::findSortFilter(), FilterBundle:src/Definition/ListDefinition.php:58-67

public function createForm(array $options = []): FormInterface
{
    return $this->form = $this->listFormFactory->create($this->definition, $options + ['datagrid' => $this]);
}

public function handleRequest(Request $request): void { $this->handleArray($request->query->all()); }

public function handleArray(array $query): void
{
    $form = $this->form ?? $this->createForm($this->formOptions);
    $input = $this->listFormFactory->submit($form, $query);        // ListInput
    $this->result = $this->queryHandler->handle($input);           // single use: a 2nd call throws LogicException
    $this->listFormFactory->addViolations($form, $this->result);
}

public function getResult(): ListResult { return $this->result ?? throw new LogicException('You must first call handleRequest()'); }
public function getPager(): Pagerfanta { return $this->pager ??= ListResultAdapter::createPager($this->getResult()); }
```
- `handleRequest()` must be callable **once** per DataGrid (handler is single-use: `FilterBundle:src/Provider/QueryHandlerInterface.php` "@throws \LogicException when called a second time"); v6 `buildForm()` + `handleRequest()` order is gone: `handleRequest()` creates the form itself if `createForm()` was not called. `getFormView()` after `handleRequest()` shows submitted data and violations.
- Per-request tweaks that v6 did on `getQueryHandler()` (e.g. ComTSo `prepareQuery` hooks) go through the provider's customizer before `handleRequest()`. `DoctrineQueryHandler::withCustomizer()` returns a **clone** and is not on `QueryHandlerInterface` (SidusDoctrineFilterBundle `src/Query/DoctrineQueryHandler.php:51-57`, reported by core.MigComtso), so `$dg->getQueryHandler()->withCustomizer(...)` alone is a no-op. Decision: **keep `setQueryHandler()` public**: `$dg->setQueryHandler($dg->getQueryHandler()->withCustomizer(fn (DoctrineQueryContext $q) => ...))`, and optionally add sugar `DataGrid::customizeQuery(\Closure $fn)` doing exactly that (must be called before `handleRequest()`; the handler must be the not-yet-used one). Keep `getQueryHandler()` public.
- `getQueryHandler()` returning `QueryHandlerInterface` no longer offers `getConfiguration()`, `getPager()`, `buildForm()`, `handleRequest()`, `handleArray()` (v6 interface). Every `datagrid.queryHandler.*` Twig usage (section 3) breaks.

**Extra form children** (`filterResetButton`, `filterSubmitButton`, `actions`). v6 added them to the builder it was given (`Model/DataGrid.php:188-193`). `ListFormType` has no hook and only `definition` is required (`FilterBundle:src/Form/Type/ListFormType.php:49-60`), but `ListFormFactory::create()` forwards `$options` to the type (`FilterBundle:src/Form/ListFormFactory.php:36`). Recommended (not tested; `[INFERENCE]` that a type extension on a `final` form type works, which is standard Symfony behaviour): add `Sidus\DataGridBundle\Form\Extension\ListFormTypeExtension` (autoconfigured via `AbstractTypeExtension::getExtendedTypes()` → `[ListFormType::class]`) that declares an optional `datagrid` option and, in `buildForm()`, moves the existing `buildResetAction/buildSubmitAction/buildDataGridActions` logic there (same field names `filterResetButton`, `filterSubmitButton`, `actions`, same `LinkType` from `Form/Type/LinkType.php`). The "visible filters" test (`:250-259`) becomes `count of non-locked filters with a mapper`: in the extension compute it from `$builder->get('filters')->all()` (the `filters` child is created by `ListFormType::buildForm`, so use a late event/priority or add the buttons in `finishView`, [INFERENCE]). Alternative without any form hook: render the submit button and reset link as plain HTML in the Twig templates and keep only `actions` through the extension.

Risk: the submitted GET form is now named `''`, so the buttons `filterSubmitButton`/`filterResetButton` appear in the query string as `filterSubmitButton=` (unchanged from v6 only if the v6 form was also name-less; v6 form name `[INFERENCE]`). `ListInput::fromArray()` ignores unknown top-level keys, but `ListFormType` sets `allow_extra_fields => true` (`ListFormType.php:56`), so this is safe.

#### 2.3 `DependencyInjection/SidusDataGridExtension.php` and `Configuration.php`

v6:
- `:16` `use Sidus\FilterBundle\DependencyInjection\Configuration as FilterConfiguration;` → **that class does not exist in v7** (`FilterBundle:src/DependencyInjection/` contains only `ListDefinitionPass.php`, `OptionalServicesPass.php`, no `Configuration.php`; the v6 one was `Configuration(string $root = 'sidus_filter')`, `git show v6.x:DependencyInjection/Configuration.php:28-31`). The extension fatals at autoload. Remove the import, `finalizeFilterConfiguration()` (`:105-121`) and `createFilterConfigurationParser()` (`:134-137`).
- `:37-50` `load()`: processes own config, then per code `finalizeConfiguration()` and `addMethodCall('addRawDataGridConfiguration', [$code, $conf])` (`:48`).
- `:84-100` inside `finalizeConfiguration()`: `query_handler` array → parsed with the FilterBundle's v6 `Configuration`; string starting with `@` → `new Reference(...)`; otherwise `UnexpectedValueException`.
- `Configuration.php:94` `->variableNode('query_handler')->end()` (free-form node), `:52` `default_form_theme` (null default), `:53-55` `default_datagrid_template` = `@SidusDataGrid/DataGrid/bootstrap4.html.twig`, `:95` `form_theme`, `:96` `form_options`, `:129` column `sort_column`.

v7 design (spec from `FilterBundle:docs/DESIGN.md:554-556`): the inline block is appended to `sidus_filter.lists` by `prepend()` under the code `__sidus_datagrid.<code>`; no runtime registration; the DataGrid configuration stores the list code.

```php
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class SidusDataGridExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());           // raw, unprocessed
        $global = $this->processConfiguration($this->createConfigurationParser(), $configs);
        $lists = [];
        foreach ($global['configurations'] as $code => $dataGridConfiguration) {
            $dataGridConfiguration = $this->resolveParent($global, $dataGridConfiguration);   // same array_merge as v6 :61-68
            if (isset($dataGridConfiguration['query_handler']) && is_array($dataGridConfiguration['query_handler'])) {
                $lists['__sidus_datagrid.'.$code] = $dataGridConfiguration['query_handler'];
            }
        }
        if ([] !== $lists) {
            $container->prependExtensionConfig('sidus_filter', ['lists' => $lists]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /* services.yaml + processConfiguration as before ... */
        foreach ($this->globalConfiguration['configurations'] as $code => $conf) {
            $conf = $this->finalizeConfiguration($code, $conf);      // see below
            $registry->addMethodCall('addRawDataGridConfiguration', [$code, $conf]);
        }
    }
}
```
and in `finalizeConfiguration()` replace `:84-100` by:

```php
$handler = $dataGridConfiguration['query_handler'] ?? throw new UnexpectedValueException("Data-grid {$code}: query_handler is required");
unset($dataGridConfiguration['query_handler']);
$dataGridConfiguration['list'] = match (true) {
    is_array($handler) => '__sidus_datagrid.'.$code,                       // inline block, appended by prepend()
    is_string($handler) && !str_starts_with($handler, '@') => $handler,    // code of a list defined under sidus_filter.lists
    default => throw new UnexpectedValueException('query_handler must be an inline sidus_filter list definition or the code of a sidus_filter list'),
};
```
Notes:
- `@service` references to a handler service (v6 `:91-93`, `new Reference`) cannot be kept: handlers are single-use objects created by the registry. The v6 doc says "Can be either a full configuration or just the code of an already defined configuration" (`Documentation/02-configuration.md:70-71`) but the v6 code only supported `@service` for the string form — the code-string form above matches the docs and is new behaviour. Decide whether to keep it (open question Q3).
- `prepend()` runs before every extension's `load()`, with unprocessed configs, hence the second `processConfiguration` call and the need to factor `parent` merging (`:61-68`) into a helper shared by `prepend()` and `load()`. `$this->globalConfiguration` is set in `load()` (`:43`) and is therefore not available in `prepend()`.
- `parent` inheritance: v6 `array_merge($parentConfig, $dataGridConfiguration)` (`:63`) copies the parent's `query_handler` array into each child, so each child would get its own `__sidus_datagrid.<code>` list (same as before, each child had its own handler). Behaviour unchanged but doubles the list definitions.
- The array under `query_handler` is **no longer validated by the DataGrid**; FilterBundle validates it at container compile (`sidus_filter.lists.*`), so errors move from the DataGrid extension to the FilterBundle config tree with the path `sidus_filter.lists.__sidus_datagrid.<code>`. The block itself must be migrated to the v7 schema (`provider`, `options`, `pagination`, `count`, `filters.<key>{type,...}`; v6→v7 key mapping in `FilterBundle:UPGRADE-7.0.md:28-45`): v6 `results_per_page`, `sortable`, `default_sort` etc. have no shorthand any more. The DataGrid's `Documentation/02-configuration.md:38-42` sample must be rewritten accordingly.
- `FilterBundle:src/DependencyInjection/ListDefinitionPass.php:33` stores raw lists in `sidus_filter.raw_lists`; the definitions are compiled at container compile / `cache:warmup`, so `__sidus_datagrid.*` appear in `debug:sidus-filter` [INFERENCE: command not read].
- Make sure the DataGrid bundle is registered **after** or independent of `SidusFilterBundle`: `prependExtensionConfig` only requires that the `sidus_filter` extension exists at merge time, not an ordering [INFERENCE].
- `Configuration.php:94` can stay a `variableNode` (the FilterBundle validates). `Configuration.php:95` `form_theme` and `:52` `default_form_theme` stay, but valid values change (section 3.5).

#### 2.4 Compiler passes
None in this bundle (`DependencyInjection/` has no pass; `SidusDataGridBundle.php` has no `build()`). Nothing to migrate. (ComTSo's `RequestScopedDatagridPass` is a *consumer* artefact, see that section.)

#### 2.5 `Twig/RendererExtension.php`
No filter-bundle class is referenced. Functions: `render_datagrid` (`:44-47`), `get_filter_columns` (`:49-51`), `page_path` (`:54-56`, implemented `:62-71`), `attributes` (`:58-60`).
- `getFilterColumns(FormView $formView, ...)` (`:88-98`) splits **every** child of `form.filters`; in v7 `form.filters` also contains the sort field (a `SortType` hidden input + one button per column, `FilterBundle:templates/form/bootstrap_6.html.twig:9-16`). It would be rendered as a filter row. Fix in the function (skip children whose `vars.block_prefixes` contains `sidus_sort`; block prefix is `sidus_sort`, `FilterBundle:src/Form/Type/SortType.php:58-61`):
  ```php
  foreach ($formView as $formItem) {
      if (in_array('sidus_sort', $formItem->vars['block_prefixes'], true)) { continue; }
      $columns[$i % $numColumns][] = $formItem; ++$i;
  }
  ```
  and in `DataGrid/bootstrap3.html.twig:15` (iterates `form.filters` directly) add the same `{% if 'sidus_sort' not in subForm.vars.block_prefixes %}` guard.
- `getPagePath()` (`:62-71`) builds `?page=N` from the current request's route + query. It still works with v7: the `page` field is a top-level hidden field of the form named `''` (`ListFormType.php:46`) and `ListInput::fromArray` reads `page` top-level. Unchanged. Note it does not preserve the sort across pages unless `request.query` contains it, which it does (`$request->query->all()`).

---

### 3. Twig templates (`Resources/views`)

Complete inventory (grep): 

| Pattern | Location |
|---|---|
| `datagrid.queryHandler.pager` | `DataGrid/bootstrap3.html.twig:3`, `DataGrid/bootstrap4.html.twig:4` |
| `datagrid.queryHandler.configuration.sortable` | `partials/table_headers.html.twig:6` |
| `datagrid.formView.sortable` | `DataGrid/bootstrap3.html.twig:58`, `DataGrid/bootstrap4.html.twig:76` (assigned to `sort`, consumed `partials/table_headers.html.twig:8` as `sort[index]`) |
| `sort[index]` | `partials/table_headers.html.twig:8` |
| `{% use '@SidusFilter/Form/fields.html.twig' %}` | `Form/bootstrap3.html.twig:1` ; bootstrap4 theme uses `@SidusFilter/Form/bootstrap4.fields.html.twig` (`Form/bootstrap4.html.twig:1`, shown in the first line of the file) |
| `sidus_order_button_widget` override | `Form/bootstrap3.html.twig:3-6` |
| `sidus_date_range_widget` override (uses `form.startDate`/`form.endDate`) | `Form/bootstrap3.html.twig` (~`:8-30`), `Form/bootstrap4.html.twig:5-23` |
| `pagerfanta(pager, 'twitter_bootstrap3')` | `DataGrid/bootstrap3.html.twig:44` |
| `pager.*` Pagerfanta API (`hasPreviousPage`, `previousPage`, `currentPage`, `nbPages`, `hasNextPage`, `nextPage`, `currentPageResults`, `currentPageOffsetStart/End`, `count`, `maxPerPage`) | `Pager/pager.html.twig:4-65`, `Pager/pager.page.html.twig`, `DataGrid/*:38-42,62-66,92` |
| `datagrid.formView.actions`, `.filterResetButton`, `.filterSubmitButton`, `form.filters` | `DataGrid/bootstrap4.html.twig:15,47,63-67`, `DataGrid/bootstrap3.html.twig:12,15,27-31,49` |

#### 3.1 Sort header cells — `partials/table_headers.html.twig`

BEFORE (`partials/table_headers.html.twig:5-16`):
```twig
{% set sortButton %}
    {% for index, columnName in datagrid.queryHandler.configuration.sortable %}
        {% if column.sortColumn == columnName %}
            {{ form_widget(sort[index], {label: column.renderLabel}) }}
        {% endif %}
    {% endfor %}
{% endset %}
{% if sortButton|trim %}{{ sortButton|raw }}{% else %}{{ column.renderLabel|raw }}{% endif %}
```
`datagrid.queryHandler.configuration.sortable` was a list of attribute paths, the index was the name of the submit button child `sortable[<index>]` (v6 `AbstractQueryHandler.php:193-203`), and `column.sortColumn` (`Model/Column.php:55-62`: explicit `sort_column` or the column code) was compared to the path.

AFTER: the sort filter is a form field `form.filters[<sortFilterName>]` whose view var `columns` is `{ key => {key, direction (asc|desc|null), primary, next} }` (`FilterBundle:src/Form/Type/SortType.php:26-43`). The allowed keys are `options.columns` of the sort filter, **keys matching `^[A-Za-z0-9_]+$`** (`FilterBundle:src/Filter/Type/SortFilterType.php:49`). A sort button is a submit button whose `name` is the field's `full_name` and whose `value` is `next` (reference rendering: `FilterBundle:templates/form/bootstrap_6.html.twig:18-22`: `name="{{ full_name }}" value="{{ column.next }}"`, `aria-sort` on the primary column).

The DataGrid knows the sort filter's public name through `ListDefinition::findSortFilter()?->publicName()` (exposed above as `DataGrid::getSortFilterName()`); it is `null` when the list has no `sort` filter (then no cell is sortable).

```twig
{# partials/table_headers.html.twig #}
{% set sortName = datagrid.sortFilterName %}
{% set sortField = sortName is not null and datagrid.formView.filters[sortName] is defined ? datagrid.formView.filters[sortName] : null %}
<thead>
<tr>
    {% for column in datagrid.columns %}
        {% set sortKey = column.sortColumn %}
        {% set sortColumn = sortField ? sortField.vars.columns[sortKey]|default(null) : null %}
        <th{{ attributes(column.attributes) }}{% if sortColumn and sortColumn.primary %} aria-sort="{{ sortColumn.direction == 'asc' ? 'ascending' : 'descending' }}"{% endif %}>
            {% if sortColumn %}
                <button type="submit" class="btn btn-link p-0{% if sortColumn.direction %} active{% endif %}"
                        name="{{ sortField.vars.full_name }}" value="{{ sortColumn.next }}">
                    {{ column.renderLabel|raw }}
                    {% if sortColumn.direction == 'asc' %}&uarr;{% elseif sortColumn.direction == 'desc' %}&darr;{% else %}&uarr;&darr;{% endif %}
                </button>
            {% else %}
                {{ column.renderLabel|raw }}
            {% endif %}
        </th>
    {% endfor %}
</tr>
</thead>
```
Notes:
- `aria-sort` belongs on the `<th>` (the spec's example puts it on the button; both are accepted by the v7 theme, `bootstrap_6.html.twig:19` puts it on the button — keep it on `<th>` for table semantics, or copy the theme).
- The sort field must be **inside** the `<form>` (`form_start`/`form_end` wrap the table in both DataGrid templates: `bootstrap4.html.twig:7-12,109-111`; `bootstrap3.html.twig:9,96`), and its hidden input must be rendered: it is (`sidus_sort_widget` renders the hidden input **and** all buttons: `bootstrap_6.html.twig:9-16`). Since the headers render their own buttons, render only the hidden input by `{{ form_widget(sortField, {…}) }}` is **not** appropriate (it would print one button group). Use `<input type="hidden" name="{{ sortField.vars.full_name }}" value="{{ sortField.vars.value }}">` once in the form (put it in the `filters` block or just before `<table>`), hidden so a filter submit keeps the current sort. Unlike v6, a click on a header button posts the new sort value as the named button; the button's own `name=value` overrides the hidden field's one only if the server takes the last occurrence — **both are posted** (`sort=…` twice). [INFERENCE] PHP keeps the last value for repeated GET names, and the submit button comes after the hidden input in document order → correct; if you prefer certainty, drop the hidden input and make header cells submit with `name/value` only (then a plain filter submit loses the sort; the hidden input is the v7 theme's behaviour). Open question Q5.
- The `name="{{ sortField.vars.full_name }}"` for the form named `''` is `filters[<sortName>]` (child `filters` is a compound child, `ListFormType.php:30`). `ListInput::fromArray($query, $sortKey)` also accepts a top-level `sort` (`FilterBundle:src/Form/ListFormFactory.php:53`); both work.
- `{{ column.renderLabel|raw }}` reuse the v6 expression (`table_headers.html.twig:15`).

**Mapping DataGrid columns → sort keys (point 4).** v6: a column is sortable iff its `sort_column` (default: the column code, `Model/Column.php:55-62`; config `DependencyInjection/Configuration.php:129`, doc `Documentation/02-configuration.md:24,144`) equals one entry of the handler's `sortable` list (attribute paths like `author.label`). v7: a column is sortable iff its `sort_column` (default column code) is a **key** of the sort filter's `options.columns` (`key => attribute path`).

```yaml
# BEFORE (v6, Documentation/02-configuration.md:23-24 + FilterBundle v6 sortable)
sidus_data_grid:
    configurations:
        news:
            columns:
                title: ~
                author: { sort_column: author.label }
            query_handler:
                sortable: [title, author.label]
                default_sort: { title: asc }        # shape [INFERENCE: UPGRADE-7.0.md:30 shows {a: desc}]

# AFTER (v7)
sidus_data_grid:
    configurations:
        news:
            columns:
                title: ~
                author: { sort_column: author_label }      # a KEY of options.columns, [A-Za-z0-9_]+ only
            query_handler:                                 # becomes sidus_filter.lists.__sidus_datagrid.news
                provider: doctrine
                options: { entity: App\Entity\News }
                filters:
                    sort:
                        type: sort
                        options:
                            columns: { title: title, author_label: author.label }
                            default: [{ column: title, direction: asc }]
```
Rules for the migration: (a) every v6 `sortable` entry `X` becomes a `columns` entry `key: X`; (b) choose `key = <DataGrid column code>` whenever the code matches the regexp, so `sort_column` can be dropped; (c) dotted/dashed `sort_column` values (v6 allowed `author.label`) **must** be renamed to an alphanumeric key and `sort_column` updated, otherwise the container build fails (`SortFilterType.php:49`); (d) a DataGrid column whose code contains `.`, `-` etc. must define `sort_column` explicitly (`Column::getSortColumn()` falls back to the code, `Model/Column.php:57-59`). UPGRADE-7.0.md:30 suggests `c: b.c` (last segment) as key; that is a convention, not a rule. The sort filter's public name can be changed with `name:`; the DataGrid reads it from `findSortFilter()` so templates never hard-code `sort`.
- `Column::getSortColumn()` stays as is. An optional hardening: `DataGrid::isSortable(Column)` = `array_key_exists($column->getSortColumn(), $this->definition->findSortFilter()?->options['columns'] ?? [])` (`FilterDefinition::$options` is public, `FilterBundle:src/Definition/FilterDefinition.php`) and use it from Twig instead of the `sortColumn|default(null)` lookup.

#### 3.2 Pager — `DataGrid/*.html.twig` and `Pager/*.html.twig`

BEFORE: `{% set pager = datagrid.queryHandler.pager %}` (`bootstrap4.html.twig:4`, `bootstrap3.html.twig:3`), `{{ pagerfanta(pager, 'twitter_bootstrap3') }}` (`bootstrap3.html.twig:44`), `{% for result in pager %}` (`bootstrap4.html.twig:83`, `bootstrap3.html.twig:74`), include of `@SidusDataGrid/Pager/pager.html.twig` (`bootstrap4.html.twig:57`).

AFTER (both DataGrid templates, line 4 / line 3):
```twig
{# @var pager \Pagerfanta\Pagerfanta #}
{% set pager = datagrid.pager %}
```
with `DataGrid::getPager()` returning `ListResultAdapter::createPager($result)` (see 2.2). The pager is **already on the result's page** with `setAllowOutOfRangePages(true)` and `maxPerPage = result.perPage()` (`FilterBundle:src/Pagerfanta/ListResultAdapter.php:26-34`) — never call `setCurrentPage()`/`setMaxPerPage()` again (the adapter only holds one page; `getSlice()` for another offset throws `LogicException`, `ListResultAdapter.php:48-50`).

What keeps working unchanged in `Pager/pager.html.twig` / `pager.page.html.twig` / the count blocks: iteration, `pager.hasPreviousPage/previousPage/currentPage/nbPages/hasNextPage/nextPage/currentPageOffsetStart/End/maxPerPage`, `pager.currentPageResults|length`, `pager.count` (and `page_path`).
Behaviour to handle:
1. `count: none` lists: `getNbResults()` returns `(page-1)*perPage + items + (hasNext ? 1 : 0)` (`ListResultAdapter.php:43`): `pager.count`/`nbPages` are *lower bounds*, not totals. The numbered pager and `sidus.datagrid.results.label` ("Displaying %length% of %count% results") become wrong; render only prev/next and a label without `%count%` when `datagrid.result.total() is null`. Add `{% if datagrid.result.total() is not null %}…{% endif %}` around `datagrid_count` blocks (`pager.html.twig:52-63` real, `bootstrap4.html.twig:36-44`, `bootstrap3.html.twig:60-67`).
2. `pager.count` triggers the lazy count (`ListResult::total()` is lazy, once): fine; with `count: auto` it costs one extra query only when the template reads it (it always does here).
3. `bootstrap3.html.twig:44` `pagerfanta(pager, 'twitter_bootstrap3')` needs `babdev/pagerfanta-bundle` (undeclared dependency in `composer.json`). v6 already had that implicit dependency; keep or replace by the bundled `@SidusDataGrid/Pager/pager.html.twig` include. Since Bootstrap 3 themes are gone (3.5), recommended: delete the bootstrap3 templates.
4. `datagrid.result` (new accessor `DataGrid::getResult(): ListResult`) gives `items()`, `violations()`, `state()` for custom templates.

#### 3.3 Filter form rendering (`filters`, `actions`, buttons blocks)

- `form.filters` iteration (`bootstrap4.html.twig:15-26` via `get_filter_columns`, `bootstrap3.html.twig:12-23`) stays but must skip the sort child (2.5). `{% if form.filters|length > 0 %}` (`:15` / `:12`) counts the sort child: use `{% if (form.filters|filter(f => 'sidus_sort' not in f.vars.block_prefixes))|length > 0 %}` or let `get_filter_columns` return `[]` and test `{% set filterColumns = get_filter_columns(form.filters, num_columns) %}{% if filterColumns %}`.
- Hidden filters (v6 `hidden` option, read at `Model/DataGrid.php:254`) → v7 `locked: true` + `default` (no form field at all, `ListFormType.php:36-38`), so there is nothing to skip in Twig.
- A hidden `page` field exists in the form (`ListFormType.php:46`): `{{ form_rest(form) }}`/`form_end(form)` renders it. After a **filter submit the page must reset to 1**: the hidden `page` carries the previous page value. `ListFormFactory::submit()` returns `page` from the form data (`ListFormFactory.php:70`), so a filter change on page 5 keeps `page=5`. v6 had the same hidden `page` in `sortable[config][page]` (`filter-bundle-review.md:122`) [INFERENCE on whether it was reset]. Decide Q6: render `<input type="hidden" name="page" value="1">` for the submit button or add `onchange` reset; at minimum set `value=1` on the `filterSubmitButton` post-processing.
- `datagrid.formView.actions`, `.filterResetButton`, `.filterSubmitButton`: unchanged names provided the extension in 2.2 is implemented.

#### 3.4 Translation keys

All `sidus.filter.*` and `sidus.datagrid.*` occurrences in this repo (grep, excluding `.idea`):

| Key | Where | v7 status |
|---|---|---|
| `sidus.filter.date_range.to` | `Resources/views/Form/bootstrap3.html.twig:16`, `Form/bootstrap4.html.twig:12` | **stays** (`FilterBundle:translations/messages.en.yaml:3`; also `start_date`, `end_date`) |
| `sidus.filter.combo.*` | **no usage in this repo** (grep found none; they were consumed by the FilterBundle v6 `ComboFilterType` / `sidus_combo_filter_widget`, `git show v6.x:Resources/views/Form/fields.html.twig:27-34`) | renamed `sidus.filter.operator.<operator>` (`UPGRADE-7.0.md:43-45`); only matters if an app overrode/re-declared these keys in its own `messages.*` — e.g. v6 `_like_` → `sidus.filter.operator.contains`. DataGrid has nothing to rename |
| `sidus.filter.sort.{asc,desc}` | new (FilterBundle) | use as `title`/`aria-label` for the sort button if wanted: `{{ ('sidus.filter.sort.' ~ sortColumn.direction)|trans }}` |
| `sidus.datagrid.reset.label` | `Model/DataGrid.php:265` (real line of the `label` default in `buildResetAction`) | stays (`Resources/translations/messages.en.yml`: `sidus.datagrid.reset.label: Reset`) |
| `sidus.datagrid.submit.label` | `Model/DataGrid.php:279` | stays |
| `sidus.datagrid.results.label` | `Pager/pager.html.twig:55`, `DataGrid/bootstrap3.html.twig:62`, `DataGrid/bootstrap4.html.twig:38` | stays; note the `bootstrap4` count block does not pass `%length%` (`bootstrap4.html.twig:38-42`) although the message uses it (`messages.en.yml`: `Displaying %length% of %count% results`) — pre-existing bug, one-line fix: add `'%length%': pager.currentPageResults|length` |
| `sidus.datagrid.boolean.{yes,no}` | `Renderer/DefaultColumnValueRenderer.php:116-117` | stays |

Translation domain: both bundles use `messages`. FilterBundle ships `en`/`fr` `messages` files (`FilterBundle:translations/`) — a key present in both bundles (`sidus.filter.*`) is defined only in FilterBundle; DataGrid defines only `sidus.datagrid.*` (`Resources/translations/messages.{en,fr}.yml`), no collision.

#### 3.5 Form themes

v6 (`Form/bootstrap3.html.twig:1`: `{% use '@SidusFilter/Form/fields.html.twig' %}`; `Form/bootstrap4.html.twig:1`: `{% use '@SidusFilter/Form/bootstrap4.fields.html.twig' %}`; documented `Documentation/01-install.md:54-56`: `form_themes: ['bootstrap_4_layout.html.twig', '@SidusDataGrid/Form/bootstrap4.html.twig']`; per-grid `form_theme` `Configuration.php:95`, applied in templates `bootstrap4.html.twig:8-10`, `bootstrap3.html.twig:6-8`).
v7: `@SidusFilter/Form/*` templates are **gone** (the files are now `@SidusFilter/form/{base,bootstrap_6,bulma_1}.html.twig`, lowercase `form`, `FilterBundle:templates/form/`; `bootstrap4.fields.html.twig` and `fields.html.twig` existed in v6, `git ls-tree v6.x`: `Resources/views/Form/{bootstrap4.fields,fields}.html.twig`). The block `sidus_order_button_widget` (`Form/bootstrap3.html.twig:3-6`) and `sidus_sort_config_*` no longer exist. The v7 `sidus_date_range_widget` uses `form.from`/`form.to`-style children (`bootstrap_6.html.twig:33-36` uses `form.from`), whereas the DataGrid themes use `form.startDate`/`form.endDate` (`Form/bootstrap4.html.twig:9,13`) — [INFERENCE: v7 date-range sub-field names are `from`/`to`; confirm in `DateRangeMapper`].

Do:
1. Delete `Form/bootstrap3.html.twig` and `Form/bootstrap4.html.twig` (both depend on removed v6 theme files and re-declare blocks that v7 provides) — or reduce `Form/bootstrap4.html.twig` to `{% use '@SidusFilter/form/bootstrap_6.html.twig' %}` plus the `sidus_link_*` blocks (the `sidus_link_widget`/`sidus_link_row` blocks used by `LinkType` live in `Form/fields.html.twig:~25-47`, which the DataGrid owns and which must stay).
2. Documentation/`01-install.md:54-56`: `twig.form_themes: ['bootstrap_5_layout.html.twig', '@SidusFilter/form/bootstrap_6.html.twig', '@SidusDataGrid/Form/fields.html.twig']` (Symfony has no Bootstrap 6 layout; `bootstrap_6` extends the Bootstrap 5 layout, `FilterBundle:docs/adapters.md` "Themes").
3. `default_form_theme` (`Configuration.php:52`, null by default) and per-grid `form_theme` strings (`bootstrap4.html.twig:8-10`) keep working: any user value pointing to `@SidusDataGrid/Form/bootstrap{3,4}.html.twig` must be changed.
4. `Form/fields.html.twig` (`sidus_link_*` blocks, `LinkType`) stays.
5. `DataGrid/bootstrap4.html.twig` is Bootstrap 4 markup (`mr-2`, `sr-only`, `input-group-prepend`, `pull-right` in bs3): either keep as-is (works with `bootstrap_6`/5 theme except `sr-only`→`visually-hidden`, `rounded-right`→`rounded-end`, `ml-1`→`ms-1`, `mr-2`→`me-2`) or rename the default template to `bootstrap5.html.twig` and change the default at `Configuration.php:54`. [INFERENCE: cosmetic only.]

---

### 4. Where the sortable declarations live (recap)
- `Column::$sortColumn` (`Model/Column.php:31`, getter `:55-62` default = code), set from config key `sort_column` (`Configuration.php:129`) via PropertyAccess in `Column::__construct` (`Model/Column.php:39-47`).
- v6 sortable list = `query_handler.sortable` (FilterBundle v6 `Query/Handler/Configuration/QueryHandlerConfiguration.php:30,97`), mapped by section 3.1 to `sidus_filter.lists.<list>.filters.<sortName>.options.columns`.

---

### 5. Final checklist (ordered by dependency)

1. `composer.json`: `sidus/filter-bundle ^7.0`, `php >=8.4`, Symfony `^8.1`, add `pagerfanta/core ^4.0`, `symfony/twig-bridge` (section 1).
2. `DependencyInjection/SidusDataGridExtension.php`: drop `FilterConfiguration` import + `finalizeFilterConfiguration()` + `createFilterConfigurationParser()`; add `PrependExtensionInterface::prepend()` that prepends `sidus_filter.lists.__sidus_datagrid.<code>`; replace `query_handler` handling with the `list` key (2.3). Refactor `parent` merge into a helper.
3. `Registry/DataGridRegistry.php`: configurations only, new `DataGrid` + new handler per `getDataGrid()`; remove `addDataGrid()` and the cache (2.1).
4. `Model/DataGrid.php`: constructor with definition/handler/`ListFormFactory`; `createForm()/handleRequest()/handleArray()/getPager()/getResult()/getSortFilterName()`; keep `setQueryHandler()` (customizer clones); drop `buildForm()`, `#[AutoconfigureTag]`; rework filter actions (2.2).
5. New `Form/Extension/ListFormTypeExtension.php` (moved `filterResetButton`, `filterSubmitButton`, `actions`), or Twig-only buttons.
6. `Twig/RendererExtension::getFilterColumns()`: skip `sidus_sort` child (2.5).
7. Templates: `partials/table_headers.html.twig` (3.1), `DataGrid/bootstrap4.html.twig` lines 4, 15, 76 and the count blocks; `DataGrid/bootstrap3.html.twig` lines 3, 12-15, 44, 58 or delete it; `Pager/*` count handling for `count: none` (3.2); delete `Form/bootstrap3.html.twig`, slim down `Form/bootstrap4.html.twig` (3.5).
8. Translations: nothing to rename in this repo; fix `%length%` in the bootstrap4 count block (3.4).
9. Documentation: `Documentation/01-install.md:54-56` (themes), `02-configuration.md:36-43,70-72` (v7 list schema, `sort` filter, `sort_column` = key), `03-usage.md:11-22` (`getDataGrid()` per request, no `buildForm($builder)`), `CHANGELOG.md` 5.0.0, README if it mentions the query handler.
10. Tests (see below).

**Files deletable:** `Form/bootstrap3.html.twig`, `DataGrid/bootstrap3.html.twig` (Bootstrap 3 + `pagerfanta(…,'twitter_bootstrap3')`), possibly `Form/bootstrap4.html.twig` (if everything moves to the v7 themes). Code removed inside files: `DataGridRegistry::addDataGrid`, `DataGrid::buildForm(FormBuilderInterface)`, `finalizeFilterConfiguration`, `createFilterConfigurationParser`.
**Files rewritten:** `Registry/DataGridRegistry.php`, `Model/DataGrid.php`, `DependencyInjection/SidusDataGridExtension.php`, `partials/table_headers.html.twig`, `DataGrid/bootstrap4.html.twig`, `Pager/pager.html.twig` (count blocks), `Twig/RendererExtension.php` (one method).
**Stays untouched:** `Model/Column.php`, `Model/CommonTrait.php`, `Renderer/*`, `Form/Type/LinkType.php`, `Form/fields.html.twig` (link blocks), `Resources/translations/*`, `Resources/config/services.yaml`, `Configuration.php` (except optionally the default template), `SidusDataGridBundle.php`, `Twig/RendererExtension` functions `render_datagrid`, `page_path`, `attributes`.

### 6. Behaviour changes visible to DataGrid users
- Each `getDataGrid()` returns a **new** object; state set on a previous instance (`setActionParameters`, `addColumn`, `setFormOptions`) is lost; call it once per request and configure that instance.
- `handleRequest()` can only be called once per DataGrid; `buildForm($builder)` is replaced by `createForm($options)`; no custom form builder, GET form is named `''` (`?filters[title]=x&page=2`, sort `filters[sort]=-date` or top-level `sort`).
- Invalid user input is a **violation shown as a form error** and falls back to the default; no more 500 on `page=abc` (`ListInput` tolerant; `FilterBundle:docs/configuration.md` "Defaults, blank values and locked filters").
- Sort: only keys of `options.columns` are accepted (v6 accepted arbitrary column in the hidden config field: `filter-bundle-review.md:72`); `sort_column` must be an alphanumeric key; multi-column sort up to `max_columns` (default 3) is possible via the value `-date,title`.
- Filters: `hidden` filters → `locked` (the default still goes through normalisation; locked filters are not in the form); v6 combo/operator option codes → v7 operators; v6 filter config shorthands removed (`UPGRADE-7.0.md:28-45`).
- Pagination: `count: none` lists have no real total; `pager.count/nbPages` are lower bounds.
- Filter submit keeps the hidden `page` (Q6).
- `@service` values for `query_handler` are no longer supported.
- Bootstrap 3/4 form themes are gone; Bootstrap 3 DataGrid template removed.

### 7. Open questions for the owner
1. Q1 — The DESIGN says `datagrid.definition.sort.columns` and `datagrid.formView.sort[key]` (`FilterBundle:docs/DESIGN.md:559-560`), while the real v7 API is `ListDefinition::findSortFilter()->options['columns']` and `formView.filters[<sortName>].vars.columns[key]` (`SortType.php:26-43`, `ListFormType.php:30`, `FilterBundle:docs/adapters.md:20-21`; UPGRADE-7.0.md:113-115 uses the latter). This document follows the code; the DESIGN text is stale.
2. Q2 — Raise PHP/Symfony floors to 8.4 / 8.1 (section 1) or keep wide ranges and let composer reject incompatible installs?
3. Q3 — Keep a string `query_handler: <list code>` (documented in `02-configuration.md:70-71`) as "use an existing `sidus_filter.lists` entry", and drop `@service` references? Proposed: yes.
4. Q4 — How should the extra form children (`filterResetButton`, `filterSubmitButton`, `actions`) be added: `ListFormTypeExtension` (proposed) vs plain HTML in templates? FilterBundle offers no builder hook.
5. Q5 — Sort submission: header buttons plus a hidden sort input (two `filters[sort]` values posted on a header click; last wins in PHP [INFERENCE]) vs buttons only. Confirm on a real request.
6. Q6 — Reset `page` to 1 when filters change? (`ListFormFactory::submit()` keeps the hidden `page`, `ListFormFactory.php:70`.)
7. Q7 — Are date-range sub-field names in v7 `from`/`to` or `startDate`/`endDate` (the DataGrid theme override at `Form/bootstrap4.html.twig:9,13` uses the latter)? Not verified in `DateRangeMapper`.
8. Q8 — Is `ListFormFactory` autowirable by id when the form adapter is optional (`OptionalServicesPass`)? Not read in detail.

### 8. Tests to add (none exist in this repo: no `Tests/` directory was found in the listing)
- **Worker-mode regression** (core of this change): in a kernel test, call `$registry->getDataGrid('news')` twice and assert `assertNotSame`; call `handleRequest()` on the first with `?filters[title]=a&page=2`, then on the second with an empty query and assert the second has no `a`, page 1, and that the first's result/form is unchanged; assert `createQueryHandler` is called per `getDataGrid()` (handler is single-use: a second `handleRequest()` on the same grid throws `LogicException`). Reboot-free simulation: loop 3 requests through one container without `kernel->reboot()`.
- Extension `prepend()`: with two grids (one inline `query_handler`, one with `parent`) assert `sidus_filter.lists` contains `__sidus_datagrid.<code>` and `DataGridRegistry` configs contain `list` and no `query_handler`.
- Twig: render `bootstrap4.html.twig` for a list with a `sort` filter: header buttons use `name="filters[sort]"`, value = `next`, `aria-sort` on the primary column only, non-sortable columns render a plain label; render with `count: none` and assert no `%count%` text; the sort field must not appear among filter rows.
- Config error: `sort_column: author.label` fails the container build with the `^[A-Za-z0-9_]+$` message.

---

## (b) ComTSo

Repository read (read-only): `/home/vincent/www/comtso` (all paths below are relative to it). v6 snippets are quoted from it; v7 facts are quoted from `SidusFilterBundle` (`v7.x`) and `SidusDoctrineFilterBundle` (`main`, work in progress) and from `/tmp/migration/datagrid.md` (section (a), DataGrid 5.0). Anything not read directly is marked `[INFERENCE]`.

**Depends on: `relation` filter type and MariaDB/MySQL support in sidus/doctrine-filter-bundle (in progress).** ComTSo's platform is MariaDB 12.3 (`compose.yaml:39` `DATABASE_URL: mysql://…?serverVersion=mariadb-12.3.0&charset=utf8mb4`, `compose.yaml:92` `image: mariadb:12.3`, `config/packages/doctrine.yaml:3` `url: '%env(resolve:DATABASE_URL)%'`, no `server_version` override at `doctrine.yaml:7`).

### 0. Inventory: what in ComTSo touches the three bundles

| What | Where |
|---|---|
| Admin screens (`sidus_admin`) | `config/packages/sidus.yaml:13-94`, routes `config/routes/sidus_admin.yaml` (unchanged by this migration) |
| DataGrid + inline filter-bundle config (`query_handler`) | `config/packages/sidus.yaml:96-239` (6 lists: `user` 99, `forum` 138, `topic` 158, `comment` 180, `photo` 200, `quote` 221). **There is no `sidus_filter:` block anywhere** (`grep` over `config/`: only `sidus.yaml`, `security.yaml` (a comment) and the generated `reference.php`). |
| Datagrid Twig | `templates/admin/datagrid.html.twig` (the only template that reads `queryHandler`/`pager`/`formView`), `templates/bundles/SidusAdminBundle/Action/list.html.twig:19` (`render_datagrid(datagrid, {...})`). The 20 `templates/admin/**/_*cell.html.twig` / `cells/*.html.twig` only mention "Datagrid cell" in a comment (grep for `datagrid|queryHandler|pager|sortable|formView` finds nothing else): they do not change. |
| PHP | `src/Admin/Action/ListAction.php`; `src/Admin/Action/{Forum,Topic,Photo,Comment}/ListAction.php`; `src/Admin/Filter/AdminRoleFilterType.php`; `src/Admin/Registry/RequestScoped{QueryHandler,DataGrid}Registry.php`; `src/DependencyInjection/RequestScopedDatagridPass.php`; `src/Kernel.php` |
| Services | `config/services.yaml:40-44` (explicit `AdminRoleFilterType` service) |
| Tests | `tests/Admin/WorkerModeTest.php`, `tests/Admin/AdminTranslationsTest.php:53-60`, URL shape in `tests/Admin/{User,Topic,Comment,Photo,Quote}AdminTest.php` (§7) |
| Docs | `AGENTS.md:197-201`, `TODO.md:17`, comment of `config/packages/sidus.yaml:1-12` |

Other custom filter types / query-handler subclasses / DataGrid services: **none** (`grep -rn "QueryHandler\|FilterType\|DataGrid\|Renderer" src` only returns the files above; the `BodyRendererInterface` hit in `src/Mail/TemplatedMailFactory.php` is unrelated). No other consumer of the filter bundle exists in `src/` (the public forum lists use `App\Pagination\Pager`, `templates/_layout/pagination.html.twig:2`, nothing to do with Sidus).

---

### 1. Bump the bundles

**`composer.json`** (current lines 20-22):

```json
"sidus/admin-bundle": "^5.0.7",
"sidus/datagrid-bundle": "^4.0.2",
"sidus/filter-bundle": "^6.0.4",
```
→
```json
"sidus/admin-bundle": "<release compatible with datagrid 5>",   // see risk R1
"sidus/datagrid-bundle": "^5.0",
"sidus/doctrine-filter-bundle": "^1.0",
"sidus/filter-bundle": "^7.0",
```
(keep `sort-packages`: `sidus/doctrine-filter-bundle` sorts between `datagrid` and `filter`.) Package name is `sidus/datagrid-bundle` (not `data-grid-bundle`); its config root stays `sidus_data_grid`.

Already compatible, nothing to change: `php >=8.4` (`composer.json:9`), `symfony/*: 8.1.*`, `doctrine/orm ^3.3` (`:15`), `doctrine/doctrine-bundle ^3.0`, `symfony/form` (`:29`), `symfony/translation` (`:47`), `twig/twig` (`:54`). The Doctrine bundle requires `doctrine/dbal ^4.0`, `doctrine/doctrine-bundle ^3.0`, `doctrine/orm ^3.3` (`SidusDoctrineFilterBundle/composer.json:48-52`); the lock has dbal 4.5.0 / orm 3.7.5 (`composer.lock`).

`pagerfanta/pagerfanta v4.10.0` is in `composer.lock:1928` only as a **transitive** dependency of filter-bundle 6 (`git show v6.x:composer.json`: `"pagerfanta/pagerfanta": ">=2.7"`). Filter-bundle 7 only *suggests* `pagerfanta/core` (`SidusFilterBundle/composer.json` `suggest`), and DataGrid 5 is documented to require `pagerfanta/core ^4.0` (`/tmp/migration/datagrid.md:21`): if that holds nothing to add in ComTSo; otherwise add `"pagerfanta/core": "^4.0"` to `composer.json` since `templates/admin/datagrid.html.twig` iterates a Pagerfanta.

**`config/bundles.php`** (v6 lines 18-20: `SidusFilterBundle`, `SidusDataGridBundle`, `SidusAdminBundle`): add the Doctrine bundle right after the filter bundle:

```php
Sidus\FilterBundle\SidusFilterBundle::class => ['all' => true],
Sidus\DoctrineFilterBundle\SidusDoctrineFilterBundle::class => ['all' => true],   // NEW
Sidus\DataGridBundle\SidusDataGridBundle::class => ['all' => true],
Sidus\AdminBundle\SidusAdminBundle::class => ['all' => true],
```
(`SidusDoctrineFilterBundle` is an `AbstractBundle`, `src/SidusDoctrineFilterBundle.php`; it registers its compilers, factory, warmer and `lint:sidus-filter` itself, `config/services.php`.) The DataGrid prepend needs the `sidus_filter` extension to exist, which `SidusFilterBundle` guarantees.

**`config/packages/twig.yaml:3`** `form_themes: ['form/theme.html.twig', 'form/rich_text.html.twig', 'form/photo_upload.html.twig']`: **no change recommended.** ComTSo renders the filter bar itself (`templates/admin/datagrid.html.twig:17-44`), has no Bootstrap (so `@SidusFilter/form/bootstrap_6.html.twig` would be wrong), and `@SidusFilter/form/base.html.twig` is a *layout* (`{% extends 'form_div_layout.html.twig' %}`, `SidusFilterBundle/templates/form/base.html.twig:2`) that would fight `form/theme.html.twig` (a `use` theme, `templates/form/theme.html.twig:1`). Blocks the bundle would have supplied and why ComTSo does not need them: `sidus_date_range_widget` (the template renders `from`/`to` by hand, §6), `sidus_sort_widget` (the template renders its own header buttons and a plain hidden input, §6). If you later want the bundle's date-range block, `{% use '@SidusFilter/form/_blocks.html.twig' %}` in `form/theme.html.twig` is the non-conflicting way (`_blocks.html.twig` is shared by all three shipped themes).

`config/reference.php` (`:1704`, `:1722`: `SidusFilterConfig`/`SidusDataGridConfig` array shapes) is auto-generated (`reference.php:3` "This file is auto-generated"): do not edit it, let `cache:clear` regenerate it `[INFERENCE: regenerated on cache:clear by Symfony 8.1]`.

Dockerfile: `Dockerfile:154-158` runs `composer run-script --no-dev post-install-cmd` (→ `cache:clear`) with a placeholder `DATABASE_URL='mysql://build:build@127.0.0.1:3306/build?serverVersion=mariadb-12.3.0&charset=utf8mb4'` and the comment "nothing connects". v7 adds two warmers: filter-bundle `DefaultsWarmer` (non-optional) and the Doctrine bundle's `ListWarmer` (`SidusDoctrineFilterBundle/config/services.php`, `kernel.cache_warmer`, resolves entity metadata and the platform via `Dialect::of(...->getDatabasePlatform())`, `src/Validation/PlatformProbe.php:65`). With `serverVersion` in the URL DBAL picks the platform without connecting `[INFERENCE]`: verify the image build still passes without a database.

---

### 2. Worker-mode workarounds that become deletable

#### 2.1 What each did (v6)

| File | What it did |
|---|---|
| `src/Admin/Registry/RequestScopedQueryHandlerRegistry.php:16-31` | `extends Sidus\FilterBundle\Registry\QueryHandlerRegistry implements ResetInterface`. Overrode `buildQueryHandler()` (`:18-25`) so the **raw configuration is kept** (v6 `unset($this->queryHandlerConfigurations[$code])` at `vendor/sidus/filter-bundle/Registry/QueryHandlerRegistry.php:98`, `unset(...raw...)` `:113`) and added `reset(): $this->queryHandlers = []` (`:27-30`) called by `kernel.reset` after each request. Reason (docblock `:9-15`): handlers are stateful; the second `handleRequest()` threw `LogicException('Pager already applied')` (`vendor/sidus/filter-bundle/Query/Handler/AbstractQueryHandler.php:335`). |
| `src/Admin/Registry/RequestScopedDataGridRegistry.php:14-47` | `extends Sidus\DataGridBundle\Registry\DataGridRegistry implements ResetInterface`. Kept every raw datagrid configuration in `$sources` (`:19-23`, v6 `unset($this->dataGridConfigurations[$code])` at `vendor/sidus/datagrid-bundle/Registry/DataGridRegistry.php:79`), rebuilt a `DataGrid` per request in `buildDataGrid()` (`:25-41`, registering the inline `query_handler` under `__sidus_datagrid.<code>` in the query-handler registry, `:32-35`), and `reset(): $this->dataGrids = []` (`:43-46`). |
| `src/DependencyInjection/RequestScopedDatagridPass.php:17-30` | Compiler pass: `REPLACEMENTS` maps `QueryHandlerRegistry` → `RequestScopedQueryHandlerRegistry` and `DataGridRegistry` → `RequestScopedDataGridRegistry` (`:19-22`); `setClass()` + `addTag('kernel.reset', ['method' => 'reset'])` on both definitions (`:26-28`). |
| `src/Kernel.php:14-18` | `build()` registers the pass (`:17`). Imports `:5`, `:7`. |
| `src/Admin/Action/ListAction.php:59-79` `restrictSortColumn()` (called at `:38`) | Docblock `:14-22`: the v6 sort column travels in the hidden field `form[sortable][config][column]` and "the filter bundle resolves [it] to a Doctrine attribute path WITHOUT checking it against the configured `sortable` list" (any property, e.g. the password hash, could be ORDER BY). The method reads the submitted column from the request (`:62-67`); if it is not in `configuration->getSortable()` (`:68`) it **rewrites the request query** to the configured default sort (column + direction `'1'` for DESC, `'0'` for ASC, `:72-78`, `$request->query->set($name, $submitted)` `:78`), so that a request without any sort also gets an explicit ORDER BY. |

#### 2.2 What replaces them in v7

- Registries: `QueryHandlerRegistry` is `final`, holds only immutable definitions and returns **a new single-use handler on every `createQueryHandler()`** (`SidusFilterBundle/src/Registry/QueryHandlerRegistry.php:70,108-119`); `DataGridRegistry` 5.0 keeps configuration only and builds a new `DataGrid` per `getDataGrid()` (`/tmp/migration/datagrid.md` §2.1). Nothing stateful lives in a service, so there is nothing to reset: the pass, both registries and `kernel.reset` tags are dead code. They would not even compile against v7 (`QueryHandlerRegistry` is `final`; `buildQueryHandler()`, `getQueryHandlerConfiguration()`, `$queryHandlers` do not exist).
- `restrictSortColumn()`: the allow-list is now structural. The sort filter accepts **public column keys only** (`SortFilterType.php:19-20`, `:97-100`: unknown key → violation "Unknown sort column, allowed: …"), maps them to attribute paths from configuration, and on any violation the whole sort falls back to the configured `default` (`AbstractQueryHandler.php:178-186`; no sort `default` → no sort besides the identifier tie-breaker). The direction can only be `asc|desc` (`SortFilterType.php:101-104`). The identifier is always appended as final tie-breaker (`docs/filter-types.md` "sort"), so "a request without any sort gets an ORDER BY" is also guaranteed.

#### 2.3 Every reference that must go (grep over the repo, excluding `vendor/`, `var/`, `.phpunit.cache`, `.playwright-mcp`)

| Reference | Action |
|---|---|
| `src/Admin/Registry/RequestScopedDataGridRegistry.php` | **delete** (then `src/Admin/Registry/` is empty: delete the directory) |
| `src/Admin/Registry/RequestScopedQueryHandlerRegistry.php` | **delete** |
| `src/DependencyInjection/RequestScopedDatagridPass.php` | **delete** (directory becomes empty); `config/services.yaml:30` `- '../src/DependencyInjection/'` in `exclude:` becomes dangling: remove that line |
| `src/Kernel.php:5` `use App\DependencyInjection\RequestScopedDatagridPass;`, `:7` `use …ContainerBuilder;`, `:14-18` `build()` | remove; keep `use MicroKernelTrait;`. Result: `class Kernel extends BaseKernel { use MicroKernelTrait; }` |
| `src/Admin/Action/ListAction.php:38` call, `:59-79` method, `:7`/`:10`/`:11` imports that become unused, docblock `:14-22` | remove / rewrite (full v7 file in §5) |
| `tests/Admin/WorkerModeTest.php:5-9` docblock mentions `RequestScopedDatagridPass` | reword (§2.4) |
| `AGENTS.md:197-201` (the "FrankenPHP worker" paragraph at `:201`, and rule 2 at `:200`: "`App\Admin\Action\ListAction` also replaces any other sort column of the request by the default one") | rewrite: workers are fixed upstream, sort allow-list is the `sort` filter's `columns` |
| `TODO.md:17` "Sidus registries state defect … then delete the request-scoped registries workaround" | delete the item |
| `config/packages/sidus.yaml:11-12` comment "`sortable` … (App\Admin\Action\ListAction also refuses any other sort column …)" | reword: "sort `columns` are the allow-list" |

#### 2.4 `tests/Admin/WorkerModeTest.php`: keep, with two changes

Read: `testEveryListCanBeServedManyTimesByTheSameContainer` (`:12-24`, 3 visits × 6 screens through `disableReboot()`) needs **no change**: it still fails if any registry/handler/datagrid is cached across requests (a second `handle()` throws; the DataGrid keeps its own once-only `handleRequest()`, `datagrid.md` §2.2).

`testAFilteredVisitDoesNotPolluteTheNextOne` (`:26-39`) **must change, otherwise it silently stops guarding anything**: `:32` visits `'/admin/users/?form[filters][username]=ali'`. In v7 the form is named `''` (`ListFormFactory.php:39` `createNamed('', …)`) so the filter lives at `filters[username]`; `ListInput::fromArray` ignores the unknown `form` key (`ListInput.php:30-41`), the "filtered" visit would be unfiltered, and the final assertion (`:38`, 2 rows) would still pass. The test also only asserts the *last* response. New version (also exercises the state that polluted v6: filter, sort, page):

```php
public function testAFilteredVisitDoesNotPolluteTheNextOne(): void
{
    $this->loginAdmin('root');
    $this->createUser('alice');
    $this->client->disableReboot();

    $rows = static fn (Crawler $c): int => $c->filter('table.table tbody tr')->count();

    self::assertSame(2, $rows($this->client->request('GET', '/admin/users/')));
    self::assertSame(1, $rows($this->client->request('GET', '/admin/users/?filters[username]=ali&sort=-email&page=1')), 'the filter is applied');
    self::assertSame(1, $rows($this->client->request('GET', '/admin/users/?page=2')), 'an out-of-range page is allowed and shows the single empty row');
    $crawler = $this->client->request('GET', '/admin/users/');
    self::assertResponseIsSuccessful();
    self::assertSame(2, $rows($crawler), 'the last visit is not polluted by the previous ones');
    self::assertSame('ascending', $crawler->filter('th[aria-sort]')->attr('aria-sort'), 'the default sort came back');
}
```
(An out-of-range page is allowed and empty in v7, `ListResultAdapter.php:26-34`; the empty table renders one `.table-empty` row, `datagrid.html.twig:89`.) Add `use Symfony\Component\DomCrawler\Crawler;`. Docblock `:5-9` → "The v7 registries create a fresh DataGrid and a single-use handler per call; this guards that no service caches them". Do **not** delete the test: it is the only guard against someone caching a handler/datagrid in a service again.

---

### 3. Configuration: `sidus_filter` / `sidus_data_grid`

Files: only `config/packages/sidus.yaml` (the `sidus_data_grid:` block, `:96-239`). v6 constructs actually used (grep of the block): `query_handler.{provider, options.entity, results_per_page, sortable, default_sort, filters}`, filter `type` ∈ {`text`, `choice`, `date_range`, `entity`, `admin_role`}, `attributes`, `label`, `form_options.{placeholder, choices}`. **Not used anywhere:** `advanced_text`, `advanced_number`, `custom_choice`, `not_null`, `hidden`, `default:`, `form_type`, `type: exact`, `options.joins`.

Decision (follows `/tmp/migration/datagrid.md` §2.3): **keep the lists inline** under `sidus_data_grid.configurations.<code>.query_handler` (no new file, same place as today, `AGENTS.md:197` stays true); the DataGrid extension `prepend()`s them to `sidus_filter.lists.__sidus_datagrid.<code>` and the **v7 list schema** is validated by the filter bundle at container compile. `columns:` is untouched except `sort_column` (below).

#### 3.1 Mapping per construct

| v6 (ComTSo) | v7 |
|---|---|
| `provider: doctrine`, `options: { entity: X }` (`sidus.yaml:101-102`) | unchanged (`UPGRADE-7.0.md:40`; `options.entity` required, `DoctrineQueryHandlerFactory.php:63-71`) |
| `results_per_page: 25` (`:103`, forum `50` `:142`) | `pagination: { per_page: 25 }` (`max_per_page` default 100) |
| `sortable: [username, …]` + `default_sort: { username: ASC }` (`:104-105`) | one filter `sort: { type: sort, options: { columns: {key: path}, default: [{column, direction}] } }`. Keys must match `^[A-Za-z0-9_]+$` (`SortFilterType.php:49`): a dotted v6 entry like `forum.title` becomes key `forum` → path `forum.title`. Direction is lower-case `asc|desc` (`SortFilterType.php:70`, `SortDirection::tryFrom`). |
| `columns.<col>.sort_column: forum.title` (`:174`, `:175`, `:195`, `:196`, `:214`) | **delete the `sort_column` lines**: a column is sortable iff its `sort_column` (default = column code) is a *key* of the sort filter (`datagrid.md` §3.1). Every ComTSo sortable column code is a valid key, so choosing `key = column code` makes `sort_column` unnecessary. |
| `filters.<k>: { type: text, label: L }` | `filters.<k>: { type: text, form: { label: L } }` (`UPGRADE-7.0.md:38`). `text` is `contains`, case-insensitive by default (`filter-types.md`), like v6's `LIKE %x%` (`git show v6.x:Doctrine/Filter/Type/TextFilterType.php`). |
| `attributes: [author.username]`, `[title, originalFilename]`, `[content, originalAuthor]` | unchanged syntax; several attributes are OR-ed (`AnyOf`, `docs/configuration.md` "Attributes"), to-one path `author.username` uses the shared `LEFT JOIN` `author_`. |
| `type: choice` + `form_options.choices` + `form_options.placeholder` | `type: choice`, `options.choices` (map label ⇒ value, validates input), `form.options.placeholder` (see the boolean trap in §3.3) |
| `type: date_range` | `type: date_range` + `options.timezone: Europe/Paris` (§3.3); input is `{from, to}` (was `{startDate, endDate}`), `UPGRADE-7.0.md:77` |
| `type: entity` (`:168`) | `type: relation` (`UPGRADE-7.0.md:35`) |
| `type: admin_role` + `attributes: [roles]` | custom type, §4 |

#### 3.2 Representative BEFORE / AFTER

**`user`** — BEFORE (`config/packages/sidus.yaml:99-122`):

```yaml
user:
    query_handler:
        provider: doctrine
        options: { entity: App\Entity\User }
        results_per_page: 25
        sortable: [username, email, enabled, registeredAt, lastActivity, lastLogin]
        default_sort: { username: ASC }
        filters:
            username: { type: text, label: admin.user.filter.username }
            email: { type: text, label: admin.user.filter.email }
            enabled:
                type: choice
                label: admin.user.filter.enabled
                form_options:
                    placeholder: admin.list.all
                    choices: { admin.user.status.enabled: true, admin.user.status.disabled: false }
            role:
                type: admin_role
                attributes: [roles]
                label: admin.user.filter.role
                form_options:
                    placeholder: admin.list.all
                    choices: { admin.user.role.admin_plural: admin, admin.user.role.member_plural: member }
            lastActivity: { type: date_range, label: admin.user.filter.last_activity }
```

AFTER:

```yaml
user:
    query_handler:
        provider: doctrine
        options: { entity: App\Entity\User }
        pagination: { per_page: 25 }
        filters:
            username: { type: text, form: { label: admin.user.filter.username } }
            email: { type: text, form: { label: admin.user.filter.email } }
            enabled:
                type: choice
                options:
                    # quoted: a YAML `false` cannot round-trip through the choice filter (§3.3)
                    choices: { admin.user.status.enabled: '1', admin.user.status.disabled: '0' }
                form: { label: admin.user.filter.enabled, options: { placeholder: admin.list.all } }
            role:
                type: admin_role            # App\Admin\Filter\AdminRoleFilterType, §4
                providers: [doctrine]
                attributes: [roles]
                form:
                    label: admin.user.filter.role
                    options:
                        placeholder: admin.list.all
                        choices: { admin.user.role.admin_plural: admin, admin.user.role.member_plural: member }
            lastActivity:
                type: date_range
                options: { timezone: Europe/Paris }
                form: { label: admin.user.filter.last_activity }
            sort:
                type: sort
                options:
                    columns: { username: username, email: email, enabled: enabled, registeredAt: registeredAt, lastActivity: lastActivity, lastLogin: lastLogin }
                    default: [{ column: username, direction: asc }]
```

**`topic`** — BEFORE (`:158-179`, extracts):

```yaml
results_per_page: 25
sortable: [title, forum.title, author.username, commentCount, createdAt, lastActivityAt]
default_sort: { lastActivityAt: DESC }
filters:
    title: { type: text, label: admin.topic.filter.title }
    forum:
        type: entity
        label: admin.topic.filter.forum
        form_options: { placeholder: admin.list.all_forums }
    author: { type: text, attributes: [author.username], label: admin.topic.filter.author }
columns:
    forum: { property_path: forum.title, sort_column: forum.title }
    author: { property_path: "author?.username", sort_column: author.username, template: admin/cells/author.html.twig }
```

AFTER:

```yaml
pagination: { per_page: 25 }
filters:
    title: { type: text, form: { label: admin.topic.filter.title } }
    forum:
        type: relation
        form: { label: admin.topic.filter.forum, options: { placeholder: admin.list.all_forums } }
    author: { type: text, attributes: [author.username], form: { label: admin.topic.filter.author } }
    sort:
        type: sort
        options:
            columns: { title: title, forum: forum.title, author: author.username, commentCount: commentCount, createdAt: createdAt, lastActivityAt: lastActivityAt }
            default: [{ column: lastActivityAt, direction: desc }]
columns:
    forum: { property_path: forum.title }          # sort_column removed: key `forum` = column code
    author: { property_path: "author?.username", template: admin/cells/author.html.twig }
```
(`relation` options beyond the attribute, e.g. `multiple`, are not read from a source file: `[INFERENCE]`; `Forum` has `__toString()` = title, `src/Entity/Forum.php:157-160`, so the `EntityType` label needs no `choice_label`.)

**The other four lists** (same recipe, only the deltas):

| List | `pagination.per_page` | `sort.options.columns` | `default` | filters (v7) |
|---|---|---|---|---|
| `forum` (`:139-146`) | `50` | `{ order: order, title: title, createdAt: createdAt }` | `[{ column: order, direction: asc }]` | `title: { type: text, form: { label: admin.forum.filter.title } }` |
| `comment` (`:181-191`) | `25` | `{ id: id, author: author.username, topic: topic.title, createdAt: createdAt }` | `[{ column: createdAt, direction: desc }]` | `content`, `author` (`attributes: [author.username]`), `topic` (`attributes: [topic.title]`) as `text` + `form.label`; `createdAt: { type: date_range, options: { timezone: Europe/Paris }, form: { label: admin.comment.filter.created_at } }`; drop `sort_column` at `:195`, `:196` |
| `photo` (`:201-210`) | `25` | `{ originalFilename: originalFilename, author: author.username, fileSize: fileSize, takenAt: takenAt, createdAt: createdAt }` | `[{ column: createdAt, direction: desc }]` | `name` (`attributes: [title, originalFilename]`), `author` as `text`; `takenAt` as `date_range` (+ `timezone`); drop `sort_column` at `:214` |
| `quote` (`:222-229`) | `25` | `{ id: id, originalAuthor: originalAuthor, createdAt: createdAt }` | `[{ column: id, direction: desc }]` | `text: { type: text, attributes: [content, originalAuthor], form: { label: admin.quote.filter.text } }` |

Public input names stay the filter keys, so ComTSo URLs keep `filters[username]=…`, only the `form[…]` prefix goes away (§7).

#### 3.3 Points to check (all verified against the code, behaviour to confirm by test)

- **Boolean `choice` (`enabled`)**: `ChoiceFilterType::resolve()` compares `(string) $choice === (string) $item` (`ChoiceFilterType.php:102`). With v6's YAML `false`, `(string) false === ''` never equals the posted `'0'`: Symfony's `ChoiceType` posts `'0'` for a `false` choice (`vendor/symfony/form/ChoiceList/ArrayChoiceList.php:58` `false === $choice ? '0' : (string) $choice`) → "not a valid choice" violation for "disabled". Hence the quoted `'1'`/`'0'` in §3.2. The string then reaches `Equals(enabled, '0')`; `ValueBinder::prepare` only coerces numeric strings for integer/float types (`SidusDoctrineFilterBundle/src/Query/ValueBinder.php:58-75`), DBAL's `BooleanType`→`convertBooleans` leaves a string untouched (`vendor/doctrine/dbal/src/Platforms/AbstractPlatform.php:1925-1938`) so MariaDB receives `enabled = '0'` and coerces `[INFERENCE]`: `UserAdminTest.php:54` (`filters[enabled]=0` → `['inactif']`) is the test that proves it.
- **`date_range` timezone**: default is `UTC` (`DateRangeFilterType.php:30`); a date-only bound is "start / last microsecond of that day in `timezone`" (`filter-types.md`), then converted to the PHP default timezone for binding (`ValueBinder.php:89`). ComTSo stores in Europe/Paris (`frankenphp/conf.d/10-app.ini:2` `date.timezone = Europe/Paris`, `config/packages/twig.yaml:9`), so set `options.timezone: Europe/Paris` or the one-hour offset moves rows around midnight. Covered by `CommentAdminTest.php:111` (`2026-02-01`..`2026-02-28`).
- **Invalid sort / filter input** is now a *violation* (`ListResult::violations()`), rendered as a form error on the closest field (`ListFormFactory::addViolations`, `ListFormFactory.php:75-80`), HTTP 200, defaults applied. `templates/admin/datagrid.html.twig:34` already prints `form_errors(filter)`; the sort field and `page` errors are not rendered (optional: add `{{ form_errors(form) }}` / `{{ form_errors(form.filters[sortName]) }}`).
- **`count`**: v7 default `auto` = one lazy `COUNT(root.id)`; the template reads `pager.nbResults` (`datagrid.html.twig:51`) so it always runs. Nothing to configure.
- **MariaDB specifics to verify against the Doctrine bundle's contract suite** (all `[INFERENCE]` until read there): `contains`/`starts_with`/`ends_with` are `LOWER(col) LIKE … ESCAPE '!'` (`docs/provider.md` "Criteria"); MariaDB's default `utf8mb4` collations are case- *and accent*-insensitive, so `contains: ecole` finds `école` where the reference (and PostgreSQL/SQLite) does not — the same looseness v6's raw `LIKE` already had (v6 only lower-cased on PostgreSQL, `git show v6.x:Doctrine/Filter/Type/AbstractSimpleFilterType.php`, `requiresCaseInsensitiveLikeTransform`), so ComTSo sees no regression, but the contract suite's "divergence" list for MariaDB is worth reading; `Equals` on `enabled` is `tinyint(1) = '0'` (above); the `roles` JSON column is `LONGTEXT` on MariaDB and the admin-role criterion is a plain `LIKE` on it (§4): no `JSON_*` function, no `REGEXP`, no `GROUP_CONCAT` is used by any ComTSo list; sort nulls-first/last is emitted by the provider as an explicit `CASE WHEN … IS NULL` key (`provider.md` "Sorting"), so nullable `lastActivity`/`lastLogin`/`takenAt`/`order` order the same on MariaDB.

---

### 4. `AdminRoleFilterType`

#### 4.1 What it does (v6)

`src/Admin/Filter/AdminRoleFilterType.php:12-29`, `extends Sidus\FilterBundle\Doctrine\Filter\Type\AbstractSimpleFilterType`. Filter of the member list on `ROLE_ADMIN`: input `admin` ⇒ `<column> LIKE '%"ROLE_ADMIN"%'`, input `member` ⇒ `<column> NOT LIKE …` (`:17-23`, parameter `uniqid('role')`); any other value is "empty" = no filtering (`isEmpty()`, `:25-28`). The column is `User::$roles`, `#[ORM\Column(type: Types::JSON)]` (`src/Entity/User.php:49-50`), stored as a JSON array string, hence the quoted-string `LIKE`. Wiring: explicit service with two positional arguments, the type code and the form widget (`config/services.yaml:40-44`: `- admin_role`, `- Symfony\Component\Form\Extension\Core\Type\ChoiceType`), used at `config/packages/sidus.yaml:115-121`.

#### 4.2 Why not `choice`, why not the built-in `native`

- `choice` emits `Equals`/`In` on the attribute (`ChoiceFilterType.php:45`, `:86-88`). The Doctrine provider **refuses** them on a JSON column: `FieldCapabilities::refusal()` returns "the type "json" cannot be compared for equality" (`SidusDoctrineFilterBundle/src/Metadata/FieldCapabilities.php:27` `OPAQUE_TYPES` includes `json`, `:46`), and text criteria (`Contains`…) only accept `string|text|ascii_string` (`:24`, `:47-49`). "Is an element of the array" is not equality anyway. Computed choices do not change that: the problem is the emitted criterion, not the choice list.
- the built-in `native` type has **no form mapper** (`docs/adapters.md:17`: "A filter type without a mapper (e.g. `native`) has no field"), so the `<select>` of the filter bar would vanish, and `NativeFilterType::normalize()` accepts any non-blank value (`NativeFilterType.php`), so `?filters[role]=whatever` would reach the builder instead of being a violation.

So: **a small custom filter type that validates `admin|member` and emits one `Native('doctrine', …)` criterion, plus a 10-line form mapper that reuses `ChoiceType`.** No new criterion class and no new compiler are needed: `NativeCompiler` (`SidusDoctrineFilterBundle/src/Compiler/NativeCompiler.php`) runs a `Closure(DoctrineQueryContext): string` returning a DQL condition, binds values with `$context->parameter()` (deterministic `:p1`, `:p2`… instead of `uniqid()`), and is registered (`config/services.php`). Compile-time capability check: `getRequiredOperations()` returns `[Native::class]`, which the Doctrine provider supports. Because the type emits only `Native`, the Doctrine validator does not resolve the `attributes` path (`ListValidator.php:100-102`: attributes only matter for field criteria), so `roles` is read by the closure itself.

A reusable `JsonContains` criterion (`Criterion` class in core + one compiler per provider, `docs/providers.md` "Checklist") only pays off if a second list needs it: not recommended for one admin filter.

#### 4.3 v7 code

`src/Admin/Filter/AdminRoleFilterType.php` (rewrite; autoconfigured because `FilterTypeInterface` is registered for autoconfiguration, `SidusFilterBundle.php` `registerForAutoconfiguration`, and `config/services.yaml:23,27-31` registers `App\` with `autoconfigure: true`; **delete the explicit definition `config/services.yaml:40-44`**, the constructor no longer takes arguments):

```php
<?php

namespace App\Admin\Filter;

use Sidus\DoctrineFilterBundle\Query\DoctrineQueryContext;
use Sidus\FilterBundle\Criterion\Criterion;
use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Filter\NormalizedValue;
use Sidus\FilterBundle\Filter\Type\AbstractFilterType;
use Sidus\FilterBundle\Input\Violation;

/**
 * Filter of the member list on ROLE_ADMIN: `admin` = administrators, `member` = everybody else. `roles` is a JSON array of
 * strings, so the role is looked for as a quoted string.
 */
final class AdminRoleFilterType extends AbstractFilterType
{
    public const ADMIN = 'admin';
    public const MEMBER = 'member';

    public static function getName(): string
    {
        return 'admin_role';
    }

    // configureOptions(): inherited no-op (AbstractFilterType), the type has no option

    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return [Native::class];
    }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) {
            return NormalizedValue::empty();
        }

        return \in_array($raw, [self::ADMIN, self::MEMBER], true)
            ? NormalizedValue::of($raw)
            : NormalizedValue::invalid(new Violation('', 'Expected "admin" or "member".', $raw));
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        $path = $filter->attributes[0]->path;   // `roles`; Definition\Field::$path (read in FieldCapabilities: `$field->field->path`)

        return new Native('doctrine', static function (DoctrineQueryContext $q) use ($path, $value): string {
            $column = $q->rootAlias().'.'.$path;
            $needle = $q->parameter('%"ROLE_ADMIN"%');

            return self::ADMIN === $value ? "{$column} LIKE {$needle}" : "{$column} NOT LIKE {$needle}";
        });
    }
}
```

`src/Admin/Filter/AdminRoleFormMapper.php` (new; autoconfigured with tag `sidus_filter.form_mapper`, `SidusFilterBundle.php` when `symfony/form` is installed; the `form: {label, options}` of the YAML is merged on top by `AbstractFormFilterMapper::addField`, `AbstractFormFilterMapper.php`, so labels/placeholder stay in `sidus.yaml` as in v6):

```php
<?php

namespace App\Admin\Filter;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Form\Mapper\AbstractFormFilterMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class AdminRoleFormMapper extends AbstractFormFilterMapper
{
    public function getFilterType(): string
    {
        return AdminRoleFilterType::getName();
    }

    public function add(FormBuilderInterface $builder, FilterDefinition $filter, mixed $data): void
    {
        $this->addField($builder, $filter, ChoiceType::class, [
            'choices' => [AdminRoleFilterType::ADMIN => AdminRoleFilterType::ADMIN, AdminRoleFilterType::MEMBER => AdminRoleFilterType::MEMBER],
            'placeholder' => '',
        ], $data);   // `form.options.choices` / `placeholder` from sidus.yaml override these defaults
    }
}
```

YAML: §3.2 (`type: admin_role`, `providers: [doctrine]`, `attributes: [roles]`). `providers: [doctrine]` makes the intent explicit: the type emits a doctrine-only `Native` (`docs/filter-types.md` "native: restrict it with `providers:`").

Behaviour kept: `?filters[role]=admin` ⇒ only administrators (`UserAdminTest.php:55`), `member` ⇒ all others (`:56`). Behaviour changed: any other non-blank value is now a violation (and the filter stays inactive) instead of silently ignored.

---

### 5. `prepareQuery()` hooks → customizers

#### 5.1 Call sites (grep `prepareQuery`)

| Site | What it adds to the QueryBuilder (v6) |
|---|---|
| `src/Admin/Action/ListAction.php:37` (call), `:45-47` (empty default hook) | nothing; runs **before** `handleRequest()` (`:39`), i.e. before criteria are applied |
| `src/Admin/Action/Topic/ListAction.php:14-23` | `->addSelect('rowForum', 'rowAuthor')->leftJoin($handler->getAlias().'.forum', 'rowForum')->leftJoin($handler->getAlias().'.author', 'rowAuthor')` (fetch-join of both to-one relations: one query per page, not per row) |
| `src/Admin/Action/Photo/ListAction.php:29-35` | `->addSelect('rowAuthor')->leftJoin($handler->getAlias().'.author', 'rowAuthor')` |
| `src/Admin/Action/Comment/ListAction.php:27-36` | `->addSelect('rowTopic', 'rowAuthor')->leftJoin(alias.'.topic', 'rowTopic')->leftJoin(alias.'.author', 'rowAuthor')` |
| `src/Admin/Action/Forum/ListAction.php` | no `prepareQuery`; only `extraViewParameters()` (`:23-35`) with one grouped query. |

`extraViewParameters()` in Forum (`:25`), Photo (`:39`), Comment (`:40`) read `$dataGrid->getQueryHandler()->getPager()->getCurrentPageResults()`; `getPager()` no longer exists on the handler → `$dataGrid->getResult()->items()` (DataGrid 5 `getResult(): ListResult`, `datagrid.md` §2.2 sketch `:149`). They already run after `handleRequest()` (`ListAction.php:39-41`), keep that order.

**Ordering trap: none.** All three hooks run before `handleRequest()` and none touches the builder afterwards; the grouped queries in `extraViewParameters()` use `EntityManager::createQuery`, not the list builder. Two v7 rules to keep: (1) a customizer must not `orderBy()`/`setMaxResults()`: the provider adds the identifier ordering only `if [] === getDQLPart('orderBy')` (`DoctrineQueryHandler.php:93-95`), so a customizer ordering would become the primary sort ahead of the `sort` filter; (2) a customizer that joins a **collection** must call `$q->requireDistinct()` (`DoctrineQueryContext.php:78`); all ComTSo hooks join to-one associations only.

#### 5.2 v7 equivalent

`withCustomizer(\Closure)` exists on `DoctrineQueryHandler` and returns a **clone** (`DoctrineQueryHandler.php:51-57`); it is not on `QueryHandlerInterface` (so keep the existing `assert`). The customizer runs on the fresh builder before criteria (`createContext()`, `:59-75`) and receives `DoctrineQueryContext` with `queryBuilder()`, `rootAlias()` (always `root`, `DoctrineQueryHandlerFactory::ROOT_ALIAS`) and `joins(): JoinRegistry` whose `aliasFor('forum')` returns the **shared** `LEFT JOIN` alias (`forum_`), creating it if absent (`JoinRegistry.php:46-54`). Use it: the same join serves the `forum.title` sort column and the fetch-join, where v6 added a second, private `rowForum` join. The count query resets the select (`DoctrineQueryHandler.php:107-112`), so the extra selects cost nothing there.

Because the clone must replace the DataGrid's handler, this relies on DataGrid 5 keeping `setQueryHandler()` public (decision recorded in `/tmp/migration/datagrid.md` §2.2; optional shortcut `DataGrid::customizeQuery(\Closure)`): `[INFERENCE]` for the exact names until DataGrid 5.0 is released.

New `src/Admin/Action/ListAction.php`:

```php
<?php

namespace App\Admin\Action;

use Sidus\AdminBundle\Action\ActionInjectableInterface;
use Sidus\AdminBundle\Action\ActionInjectableTrait;
use Sidus\AdminBundle\DataGrid\DataGridHelper;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\DataGridBundle\Model\DataGrid;
use Sidus\DoctrineFilterBundle\Query\DoctrineQueryContext;
use Sidus\DoctrineFilterBundle\Query\DoctrineQueryHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Every list of the admin panel (`controller_pattern` of config/packages/sidus.yaml). The sort column allow-list is the `sort`
 * filter of the list (`options.columns`); a screen adds the joins/selects of the columns it shows with queryCustomizer().
 */
#[AsController]
class ListAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __construct(
        private readonly DataGridHelper $dataGridHelper,
        private readonly TemplatingHelper $templatingHelper,
    ) {
    }

    public function __invoke(Request $request): ActionResponseInterface
    {
        $dataGrid = $this->dataGridHelper->buildDataGridForm($this->action, $request);   // admin-bundle release for datagrid 5: R1
        if (null !== $customizer = $this->queryCustomizer()) {
            $handler = $dataGrid->getQueryHandler();
            \assert($handler instanceof DoctrineQueryHandler);
            $dataGrid->setQueryHandler($handler->withCustomizer($customizer));   // [INFERENCE] DataGrid 5 API
        }
        $dataGrid->handleRequest($request);

        return $this->templatingHelper->renderListAction($this->action, $dataGrid, $this->extraViewParameters($dataGrid));
    }

    /**
     * Run on the fresh query before the filters are applied: a screen adds the joins/selects of the columns it shows (no query per row).
     *
     * @return (\Closure(DoctrineQueryContext): void)|null
     */
    protected function queryCustomizer(): ?\Closure
    {
        return null;
    }

    /** @return array<string, mixed> */
    protected function extraViewParameters(DataGrid $dataGrid): array
    {
        return [];
    }
}
```

`Topic/ListAction.php` (replace `prepareQuery`, imports `DoctrineQueryHandlerInterface`/`DataGrid` go away; `use Sidus\DoctrineFilterBundle\Query\DoctrineQueryContext;`):

```php
protected function queryCustomizer(): \Closure
{
    return static function (DoctrineQueryContext $q): void {
        $q->queryBuilder()->addSelect($q->joins()->aliasFor('forum'), $q->joins()->aliasFor('author'));
    };
}
```
`Comment/ListAction.php`: same with `aliasFor('topic')`, `aliasFor('author')`. `Photo/ListAction.php`: `addSelect($q->joins()->aliasFor('author'))`. All paths are `ManyToOne` (`Topic.php:53,57`, `Comment.php:28,32`, `Photo.php:73`). Photo and Comment keep their constructors (EntityManager) and swap `getPager()->getCurrentPageResults()` for `getResult()->items()`:

```php
// Comment/ListAction.php:40, BEFORE
$ids = array_map(static fn (Comment $comment) => $comment->getId(), iterator_to_array($dataGrid->getQueryHandler()->getPager()->getCurrentPageResults()));
// AFTER
$ids = array_map(static fn (Comment $comment) => $comment->getId(), $dataGrid->getResult()->items());
```
(Forum `:25` and Photo `:39` likewise.)

---

### 6. Templates / Twig / controllers touching pager, sortable, formView

Controllers: none (`src/Controller/AdminController.php` only uses `AdminRegistry`, `:6`, `:13`). DataGrid template mechanics (`getResult`, `getPager`, `sortFilterName`, `formView`) are defined in section (a) (`/tmp/migration/datagrid.md` §3); these are the **ComTSo-local overrides** that must follow.

`sidus_data_grid.default_datagrid_template: admin/datagrid.html.twig` (`config/packages/sidus.yaml:97`) and `templates/bundles/SidusAdminBundle/Action/list.html.twig:19` `render_datagrid(datagrid, {action, admin, usage})`: **unchanged**.

`templates/admin/datagrid.html.twig` is a full local override (the bundle's bootstrap4 template is not used). v6 touchpoints and v7 replacement:

| Line (v6) | v6 | v7 |
|---|---|---|
| `:7` | `datagrid.queryHandler.pager` | `datagrid.pager` (Pagerfanta already on the right page, `ListResultAdapter::createPager`; `nbResults`, `nbPages`, `currentPage`, `hasPreviousPage`, `hasNextPage`, `for result in pager` all still work) |
| `:9` | `datagrid.queryHandler.sortConfig` | gone: applied sort = `datagrid.result.state[<sort filter name>]` = `list<{column, direction}>` (`AbstractQueryHandler.php:194` stores each normalised value under the filter's public name) |
| `:10` | `datagrid.queryHandler.configuration.sortable` | gone: the keys are `form.filters.sort.vars.columns` (`SortType.php`: `{key, direction, primary, next}`) |
| `:14` | `pageQuery` built as `form[filters][…]` + `form[sortable][config][…]` | flat query (the form is named `''`): `filters`, `sort`, `page` |
| `:17`, `:20-37` | `form.filters\|length > 0`, loop over `form.filters` | `form.filters` now **also contains the sort child** (`ListFormType.php`, `SortMapper`): skip it |
| `:21-27` | `filter.startDate` / `filter.endDate` | `filter.from` / `filter.to` (`DateRangeType.php`) |
| `:47-48` | hidden `form.sortable.config.column/direction` | one hidden `sort` input |
| `:61-67` | `sortIndex` loop + `form.sortable[sortIndex].vars.full_name` | `sortField.vars.columns[column.sortColumn]` |

BEFORE (`datagrid.html.twig:7-14`):

```twig
{% set pager = datagrid.queryHandler.pager %}
{% set form = datagrid.formView %}
{% set sortConfig = datagrid.queryHandler.sortConfig %}
{% set sortable = datagrid.queryHandler.configuration.sortable %}
…
{% set pageQuery = {(form.vars.name): {filters: app.request.query.all(form.vars.name).filters|default({}), sortable: {config: {column: sortConfig.column, direction: sortConfig.direction ? 1 : 0}}}} %}
```
AFTER:

```twig
{% set pager = datagrid.pager %}
{% set form = datagrid.formView %}
{% set sortName = datagrid.sortFilterName %}   {# 'sort'; DataGrid 5 accessor (datagrid.md §2.2) #}
{% set sortField = sortName and form.filters[sortName] is defined ? form.filters[sortName] : null %}
{# the applied sort (never the raw, possibly invalid, input) as the API value `-email,username` #}
{% set sortParts = [] %}
{% for item in datagrid.result.state[sortName]|default([]) %}
    {% set sortParts = sortParts|merge([(item.direction == 'desc' ? '-' : '') ~ item.column]) %}
{% endfor %}
{% set sortValue = sortParts|join(',') %}
{% set currentFilters = app.request.query.all('filters')|default({})|filter((value, key) => key != sortName) %}
{% set pageQuery = {filters: currentFilters, sort: sortValue} %}
```
The `{{ path(route, routeParams|merge(pageQuery)|merge({page: …})) }}` links (`:104`, `:114`, `:122`) stay as they are.

BEFORE (`:17`, `:20-21`):
```twig
{% if form.filters|length > 0 %}
…
{% for filter in form.filters %}
    {% if filter.startDate is defined and filter.endDate is defined %}
```
AFTER:
```twig
{% set filterFields = form.filters|filter(f => 'sidus_sort' not in f.vars.block_prefixes) %}
{% if filterFields|length > 0 %}
…
{% for filter in filterFields %}
    {% if filter.from is defined and filter.to is defined %}
        … form_widget(filter.from, …) … form_widget(filter.to, …)
```
(Keep the aria labels at `:25`, `:27`; `sidus.filter.date_range.*` placeholders are overridden to `''` there anyway.)

BEFORE (`:46-48`): two hidden inputs `form.sortable.config.column.vars.full_name` / `.direction…`.
AFTER — **one top-level `sort`, not the field's own name**: header buttons post `filters[sort]` and `ListInput::fromArray` lets `filters.sort` win over a top-level `sort` (`ListInput.php:36-38`), so a click overrides the hidden current sort, and a plain filter submit keeps it. Using the field's own name for both would send the same name twice (browser: last wins, i.e. the button, because it comes later; Symfony `DomCrawler` in the tests: `[INFERENCE]` not guaranteed):

```twig
{# the current sort, submitted again with the filters; a header button posts filters[sort] and wins; the page is deliberately not kept #}
<input type="hidden" name="sort" value="{{ sortValue }}">
```

BEFORE (`:60-72`):
```twig
{% set sortIndex = null %}
{% for index, name in sortable %}{% if name == column.sortColumn %}{% set sortIndex = index %}{% endif %}{% endfor %}
<th scope="col"{% if sortIndex is not null and sortConfig.column == column.sortColumn %} aria-sort="{{ sortConfig.direction ? 'descending' : 'ascending' }}"{% endif %}{{ attributes(column.attributes) }}>
    {% if sortIndex is not null %}
        <button type="submit" class="table-sort" name="{{ form.sortable[sortIndex].vars.full_name }}">{{ column.renderLabel }}</button>
```
AFTER:
```twig
{% set sortColumn = sortField ? sortField.vars.columns[column.sortColumn]|default(null) : null %}
<th scope="col"{% if sortColumn and sortColumn.primary %} aria-sort="{{ sortColumn.direction == 'asc' ? 'ascending' : 'descending' }}"{% endif %}{{ attributes(column.attributes) }}>
    {% if sortColumn %}
        <button type="submit" class="table-sort" name="{{ sortField.vars.full_name }}" value="{{ sortColumn.next }}">{{ column.renderLabel }}</button>
```
`column.sortColumn` is the column code unless `sort_column` is set (`datagrid.md` §3.1, `Model/Column.php`), hence §3.2 removes every `sort_column`. `form_end(form, {render_rest: false})` (`:95`) is kept: it keeps the hidden `page` out of the filter submit, so "a new filter starts at page 1" (`:46`) still holds. `templates/admin/datagrid.html.twig:1-5` header comment: update the description of the sort state.

Other ComTSo-local: `translations/messages.fr.yaml:182` `sidus:` tree (`sidus.admin.*`) does not collide with the filter bundle's `sidus.filter.*` keys (`date_range.{to,start_date,end_date}`, `operator.*`, `sort.{asc,desc}` live in the bundle's own file, `docs/adapters.md:26-27`): nothing to add. The datagrid column keys `datagrid.<screen>.<column>` are unchanged.

---

### 7. Tests that encode the v6 request shape (update with the migration)

Mapping: `['form' => ['filters' => X]]` → `['filters' => X]`; `['form' => ['sortable' => ['config' => ['column' => C, 'direction' => '0'|'1']]]]` → `['sort' => 'C']` / `['sort' => '-C']` (v6 direction `'1'` = DESC, `ListAction.php:76`); `startDate`/`endDate` → `from`/`to`; dotted sort columns → keys (`forum.title` → `forum`, `author.username` → `author`).

| Test | Lines | Change |
|---|---|---|
| `tests/Admin/UserAdminTest.php` | `:36` filter email; `:37-38` sort `email` `'0'`/`'1'`; `:53-56` filters username/enabled/role; `:57` `lastActivity` `startDate`/`endDate`; `:60-77` `testASortColumnThatIsNotConfiguredIsIgnored` (columns `password`, `nope.nope` → default asc, `username` + `'1'` → `-username`; expectations unchanged); `:99` + `:104-106` pagination test | `'sort' => '-email'` etc.; `:104` `$query['filters']['role']`; `:105` `assertSame('-username', $query['sort'])` (links carry the flat `sort`, §6); `:106` "a page link must not press a sort button" is obsolete (a link carries no button): replace by `assertSame('2', $query['page'])` |
| `tests/Admin/TopicAdminTest.php` | `:34-36` filters title/author/forum (`forum` = `musique` = the forum slug id, `Forum::$id` `src/Entity/Forum.php:16-18`); `:50` `['forum.title' => …, 'author.username' => …]` | `['filters' => …]`; loop keys `forum`, `author` with `['sort' => $column]` |
| `tests/Admin/CommentAdminTest.php` | `:107`, `:111`; `:124-130` allow-list | `['filters' => $filters]`; `['from' => '2026-02-01', 'to' => '2026-02-28']`; `$sort = fn ($c, $d) => … ['sort' => ('1' === $d ? '-' : '').$c]`, columns `createdAt`, `author` (was `author.username`), unknown `content` / `author.password` must still give the default (latest first) |
| `tests/Admin/PhotoAdminTest.php` | `:76-77` | `['filters' => …]` |
| `tests/Admin/QuoteAdminTest.php` | `:29` | `['filters' => ['text' => 'alphonse']]` |
| `tests/Admin/WorkerModeTest.php` | `:32` | §2.4 |
| `tests/Admin/AdminTranslationsTest.php` | `:53-60` `$grid->getQueryHandler()->getConfiguration()->getFilters()` + `$filter->getLabel()` | `foreach ($grid->getDefinition()->filters as $filter) { if ('sort' === $filter->type) { continue; } $label = $filter->form['label'] ?? null; self::assertNotNull($label, "filter $code.{$filter->key} has no label"); $need($label); }` (`DataGrid::getDefinition()` per `datagrid.md` §2.2 sketch `:129`; `FilterDefinition::$form`, `$key`, `$type` are public readonly, `FilterDefinition.php:23-34`) |
| `UserAdminTest::testASortButtonFlipsTheDirection` (`:79-90`) | `button.table-sort` first button, `th[aria-sort]` | no change in code; verify: default `username` asc → `next` of the primary column is `-username` (`SortType.php` `next`), the click posts `filters[sort]=-username` |

New tests worth adding: `filters[role]=bogus` → 200, no filtering, an error on the role field; `?sort=-nope` → default sort and 200.

---

### 8. Checklist (ordered by dependency)

1. **Prerequisites available**: `sidus/filter-bundle` 7.0, `sidus/doctrine-filter-bundle` 1.0 (with `relation` and MariaDB), `sidus/datagrid-bundle` 5.0, an `sidus/admin-bundle` release requiring datagrid 5 (R1).
2. `composer.json` (§1), `composer update sidus/*`; `config/bundles.php` (+ `SidusDoctrineFilterBundle`).
3. Delete the workaround: `src/Admin/Registry/`, `src/DependencyInjection/RequestScopedDatagridPass.php`, `Kernel::build()` (+ imports), `config/services.yaml:30` (§2.3).
4. `config/packages/sidus.yaml`: rewrite the six `query_handler` blocks (§3), drop all `sort_column`; update header comment `:1-12`.
5. `src/Admin/Filter/AdminRoleFilterType.php` rewrite + new `AdminRoleFormMapper.php`; delete `config/services.yaml:40-44` (§4).
6. `src/Admin/Action/ListAction.php` (drop `restrictSortColumn`, `queryCustomizer()`), the three subclasses + `getResult()->items()` in Forum/Photo/Comment (§5).
7. `templates/admin/datagrid.html.twig` (§6).
8. Tests (§2.4, §7): `WorkerModeTest`, `AdminTranslationsTest`, URL shapes.
9. `bin/console cache:clear` / `cache:warmup` (`DefaultsWarmer`, Doctrine `ListWarmer`: any refused criterion/field or invalid default fails here, naming the list and filter), `bin/console debug:sidus-filter` (lists `__sidus_datagrid.*`, `[INFERENCE]` per `datagrid.md:217`) and, if the Doctrine bundle's `lint:sidus-filter` exists (`SidusDoctrineFilterBundle/config/services.php`, needs `symfony/console`), run it.
10. Run the admin suite (`tests/Admin`, `tests/Security/CsrfTest.php` is unaffected: it audits routes/methods, `sidus_admin.*`), then the Docker build (`Dockerfile:154-158`, §1).
11. Docs: `AGENTS.md:197-201`, `TODO.md:17`, `config/packages/sidus.yaml:1-12`.

**Files deletable:** `src/Admin/Registry/RequestScopedDataGridRegistry.php`, `src/Admin/Registry/RequestScopedQueryHandlerRegistry.php`, `src/DependencyInjection/RequestScopedDatagridPass.php` (+ both now-empty directories), `config/services.yaml:40-44` and `:30`, `ListAction::restrictSortColumn()` (`:59-79`), `Kernel::build()` (`:14-18`). **What stays:** `tests/Admin/WorkerModeTest.php` (edited), `config/routes/sidus_admin.yaml`, the whole `sidus_admin:` block (`sidus.yaml:13-94`), all cell templates, `columns:` of every list (minus `sort_column`), `extraViewParameters()` grouped queries, the `twig.yaml:3` form themes.

### 9. Risks and open questions for the owner

- **R1 — `sidus/admin-bundle` blocks the bump.** `vendor/sidus/admin-bundle/composer.json:34` requires `"sidus/datagrid-bundle": "~4.0"`, and `DataGridHelper` (`vendor/sidus/admin-bundle/DataGrid/DataGridHelper.php:60`, `:87`) calls `$dataGrid->buildForm($builder)` and `->handleRequest()` on a caller-supplied builder, which DataGrid 5 replaces by `createForm()` (`datagrid.md` §2.2, "not designed for a caller-supplied builder"). ComTSo's `ListAction` (`:36`) goes through it. Needs an admin-bundle release (version number unknown) before step 2; until then ComTSo cannot resolve `^5.0` for the datagrid.
- **R2 — DataGrid 5 API names used here are from section (a)'s plan, not from released code** (`[INFERENCE]`): `DataGrid::setQueryHandler()` kept public, `getResult()`, `getPager()`, `getDefinition()`, `getSortFilterName()`, `formView` shape. Re-check §5/§6/§7 against the released API.
- **R3 — URL contract change.** `form[filters][x]`/`form[sortable][config][…]` bookmarks stop working (admin-only, so low impact); the v7 form is named `''` and a header button posts `filters[sort]`.
- **R4 — Sort fallback is wider than v6.** v6 replaced an invalid *column* and set a direction itself; v7 replaces the whole sort by the configured `default` (including direction) and adds a violation. The ComTSo tests (`UserAdminTest.php:60-77`, `CommentAdminTest.php:124-130`) expect exactly the default order, which v7 gives.
- **R5 — The `relation` form field** loads its choices through the Doctrine bundle's mapper; v6's entity filter restricted choices to forums reachable from the query (`EntityFilterType::getFormOptions`, `git show v6.x:Doctrine/Filter/Type/EntityFilterType.php`). Confirm the v7 mapper lists the same set for `topic.forum` (all forums with topics vs all forums) `[INFERENCE]`.
- **R6 — `enabled` boolean choice and `date_range` timezone** need the tests named in §3.3 to pass on MariaDB; they are the two places where a silent semantic difference is plausible.
- **Q1 — Keep the lists inline or move them to `sidus_filter.lists`?** This note keeps them inline (smallest diff, matches `AGENTS.md:197`). Moving them to a `config/packages/sidus_filter.yaml` would give clean list codes (`user` instead of `__sidus_datagrid.user`) and make `query_handler: user` (code string) usable if DataGrid 5 keeps that form (`datagrid.md` Q3).

---

## (c) EAV filter bundle 3.0

Scope: `/tmp/eavf` (github `VincentChalnot/SidusEAVFilterBundle`, branch `v2.0-dev`, HEAD `25a9e04`, **2018-12-18**, not 2019) → `sidus/eav-filter-bundle` **3.0** on `sidus/filter-bundle ^7.0` + `sidus/doctrine-filter-bundle`.
Read-only analysis; nothing was edited. Paths without a prefix are relative to `/tmp/eavf`. Prefixes: `MODEL:` = `/tmp/eavgit` (SidusEAVModelBundle, branch `v1.3-dev`, HEAD `6e240c6`, 2021-05-17; the filter bundle requires `dev-v1.2-dev`, `composer.json:28`), `DOCTRINE:` = `/home/vincent/www/sidus-bundles/SidusDoctrineFilterBundle` (HEAD `f4c755d` **plus an uncommitted working tree that changed under me while I read**: named joins, JSON paths, `asForeignKey()`; every DOCTRINE line number is from the last read and can drift), `CORE:` = `/home/vincent/www/sidus-bundles/SidusFilterBundle` (v7.x, HEAD `e0c60ff`), `V6:` = `git show v6.x:<path>`.
Legend: **[D]** derived from code I read (cited), **[P]** proposal (design decision, not code that exists), **[I]** inference. Inside a section about the EAV model bundle, bare `AttributeQueryBuilder.php`, `EAVQueryBuilder.php`, `OptimizedDataLoader.php` mean the `MODEL:Doctrine/…` files; bare `EAVQueryHandler.php`, `AdvancedTextFilterType.php`, `EAVFilterHelper.php` etc. are `/tmp/eavf` files; within the Doctrine-bundle sections (2.2.3, M1–M8) bare `src/…` paths are `DOCTRINE:`.

### 0. Headlines

1. **The 2018 bundle cannot load on v6.0.4 already** [D]. It is a rewrite to 3.0, not a port (proofs in 1.3).
2. **Nothing of the EAV bundle's logic survives as code**; what survives is its *behaviours*, which map onto v7 as: a field resolver (attribute code → value-table steps), a root condition (`family`), a result hydrator (data loader), two filter types (`eav_choice`, `eav_data`), a form label extension.
3. **The Doctrine bundle has no extension point for any of the three "dialect" hooks today** [D]: `FieldResolver`, `DoctrineQueryHandler`, `DoctrineQueryHandlerFactory` are `final`, the factory does `new FieldResolver(...)` itself, `'doctrine'` is hard-coded at 11 sites in 7 files, `ResolvedStep` cannot carry a join condition (`attributeCode = :code`), there is no root hook and no result hook. Section 2.2.3 lists the 8 missing pieces (M1–M8) with proposed signatures as a reference for the future EAV provider; they are **not part of the 1.0 release**.
4. **Prerequisite outside both bundles** [D]: `sidus/eav-model-bundle` itself is pre-ORM 3 / pre-DBAL 4 / pre-Symfony 8 (annotations `@ORM\…` in `MODEL:Entity/AbstractData.php`, `Doctrine\Common\Persistence` in `MODEL:Resources/config/services/doctrine.yml`, platform event manager in `MODEL:Doctrine/Types/FamilyType.php:41`, array access on `associationMappings` in `MODEL:Event/DoctrineMetadataListener.php:51-63`, php `>=5.6` / `doctrine/orm ^2.5` in `MODEL:composer.json:26,29`). A ported model bundle is a hard dependency of 3.0 (plan step 2 in 4.3).
5. **Worker-mode hazards live in the EAV model bundle** (`OptimizedDataLoader` keeps `$loadedEntityIds` and `$currentContext` in a shared service, static query-builder registries). The 3.0 hydrator must not depend on that state (2.3).
6. Semantics change in 11 listed places (section 5). The important one: to-many attributes keep "any element, criteria independent" (the property the per-condition joins protected), single-valued attributes get a shared LEFT JOIN.

---

### 1. Inventory

#### 1.1 Classes (every file of the repo)

| File | Extends / implements | Role |
|---|---|---|
| `SidusEAVFilterBundle.php:11` | `Symfony\Component\HttpKernel\Bundle\Bundle` | empty bundle class |
| `DependencyInjection/SidusEAVFilterExtension.php:12-14` | `Sidus\BaseBundle\DependencyInjection\SidusBaseExtension` (sidus/base-bundle, **not** in this `composer.json`; not on this machine) | empty body: there is no `load()` here, so the YAML files of `Resources/config/services/` are loaded by the base class [I: not read, sidus/base-bundle is absent] |
| `Factory/EAVQueryHandlerFactory.php:18` | implements v6 `Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface` (`:10`) | `createQueryHandler()` `:63-74` builds `EAVQueryHandler` with 5 services; `getProvider()` `:79-82` returns `'sidus.eav'` (non-static) |
| `Query/Handler/EAVQueryHandlerInterface.php:20` | extends v6 `Query\Handler\Doctrine\DoctrineQueryHandlerInterface` (`:11`) | `getFamily()` `:26`, `getEAVAttributeQueryBuilder()` `:34-37`, `getEAVAttributes()` `:45`, `isEAVFilter()` `:52`, `getQueryContext()` `:57`, `getResultContext()` `:62` |
| `Query/Handler/EAVQueryHandler.php:35` | extends v6 `Query\Handler\Doctrine\DoctrineQueryHandler`, implements the interface above | see 2.1 |
| `Pager/Adapter/EAVAdapter.php:15` | implements `Pagerfanta\Adapter\AdapterInterface` | wraps v6 `Pagination\DoctrineORMAdapter` and calls the data loader on each slice |
| `Filter/EAVFilterHelper.php:16` | none (plain service) | attribute-path → EAV query builders (`:39-67`), attribute-path → `AttributeInterface[]` (`:79-106`) |
| `Filter/Type/AbstractEAVFilterType.php:16` | extends v6 `Filter\Type\AbstractFilterType` | `getProvider()` `:32-35` = `'sidus.eav'`; `setFallbackFilterType()` `:24`; default form options from the attribute `:58-77` |
| `Filter/Type/AbstractSimpleFilterType.php:17` | extends `AbstractEAVFilterType` | `handleData()` `:25-50`: non-EAV filter → fallback type (`:30-33`), else one attribute query builder per attribute, OR-ed (`:42-49`) |
| `Filter/Type/TextFilterType.php:10`, `ExactFilterType.php:10`, `ChoiceFilterType.php:14`, `AdvancedTextFilterType.php:10`, `AdvancedNumberFilterType.php:10` | extend `AbstractSimpleFilterType` | one operator application each (2.5) |
| `Filter/Type/DateRangeFilterType.php:15` | extends `AbstractEAVFilterType` | `gte` + `lte` on one cloned attribute query builder (`:40-51`) |
| `Filter/Type/AutocompleteDataFilterType.php:13` | extends the EAV `ChoiceFilterType` | adds the `attribute` form option (`:36-42`), one attribute only (`:30-34`) |

Not in this repo [D]: no compiler pass, no `Configuration` class (so `sidus_filter.configurations.*` comes from the core bundle; the EAV options are free-form `options`), no tests, no templates, no translations.

From the **EAV model bundle** it uses: `Registry\FamilyRegistry`, `Model\FamilyInterface` (`getCode`, `getDataClass`, `getAttribute`, `hasAttribute`, `getContext`), `Model\AttributeInterface` (`getOption('allowed_families')`, `getFormOptions`, `__toString`), `Doctrine\EAVQueryBuilder(Interface)` and `AttributeQueryBuilder(Interface)`, `Doctrine\DataLoaderInterface`, `ContextualizedDataLoaderInterface`, `Entity\DataRepository`, `Exception\MissingAttributeException` / `MissingFamilyException` (imports at `Query/Handler/EAVQueryHandler.php:9-18`, `Filter/EAVFilterHelper.php:5-11`). Also `Sidus\EAVBootstrapBundle\Form\Type\AutocompleteDataSelectorType` (`Resources/config/services/filter_type.yml:6`, sidus/eav-bootstrap-bundle: not on this machine).

From the **v6 filter bundle**: `Factory\QueryHandlerFactoryInterface`, `Query\Handler\{AbstractQueryHandler,Doctrine\DoctrineQueryHandler(Interface),Configuration\QueryHandlerConfigurationInterface,QueryHandlerInterface}`, `Registry\FilterTypeRegistry`, `Filter\{FilterInterface,Type\AbstractFilterType,Type\FilterTypeInterface}`, `DTO\SortConfig`, `Pagination\DoctrineORMAdapter`, `Form\Type\{DateRangeType,ComboFilterType}`, `Exception\BadQueryHandlerException`.

#### 1.2 Services, tags, config

| Where | What |
|---|---|
| `Resources/config/services/factory.yml:2-3` | **`alias: Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface` → `EAVQueryHandlerFactory`** (an interface alias to a concrete factory: any consumer autowiring that interface gets the EAV one; a bug, not a feature) |
| `factory.yml:5-14` | `EAVQueryHandlerFactory`, private, args `FilterTypeRegistry`, `@sidus_eav_model.entity_manager` (deprecated in the model bundle: `MODEL:Resources/config/services/doctrine.yml:2-8`), `FamilyRegistry`, `EAVFilterHelper`, `DataLoaderInterface`; tag `sidus.query_handler_factory` |
| `Resources/config/services/helper.yml:2-4` | `EAVFilterHelper`, `autowire: true` |
| `Resources/config/services/filter_type.yml` | 7 services tagged `sidus.filter_type`: `autocomplete_data` (`:2-8`, form `AutocompleteDataSelectorType`), `date_range` (`:10-18`), `text` (`:20-28`), `choice` (`:30-38`), `exact` (`:40-48`), `advanced_text` (`:50-60`, `ComboFilterType`, `options_choices: '%sidus.filter.advanced_text_choices%'`), `advanced_number` (`:62-72`). Each but the first calls `setFallbackFilterType` with the v6 Doctrine type of the same name (`:16,26,36,46,58,70`) |
| Config | none declared. Options read from `QueryHandlerConfiguration->getOption()`: `family` (`Query/Handler/EAVQueryHandler.php:80`, required), `enforce_family_condition` (default true, `:106`, `:149`), `query_context` (`:167`), `use_global_context` (`:168`), `result_context` (`:182`), `loader_depth` (default 2, `:232`). Documented in `README.md:46-72` (example) and `:86-100` (reference) |
| Provider name | `sidus.eav` (`Factory/EAVQueryHandlerFactory.php:81`, `Filter/Type/AbstractEAVFilterType.php:34`) |

`allowed_families` is **not a filter-bundle option**: it is an *attribute* option of the EAV model (`sidus_eav_model.families.<F>.attributes.<a>.options.allowed_families`), read by `EAVFilterHelper.php:53` and `:88` to follow a relation attribute to its target family. It stays where it is.

#### 1.3 Why it does not load on v6.0.4 today [D]

| EAV code | v6.0.4 contract |
|---|---|
| `getProvider(): string` non-static (`Factory/EAVQueryHandlerFactory.php:79`, `Filter/Type/AbstractEAVFilterType.php:32`) | `public static function getProvider()` (`V6:Factory/QueryHandlerFactoryInterface.php:31`, `V6:Filter/Type/FilterTypeInterface.php:36`): fatal "cannot make static method non static" |
| `protected function applySort(SortConfig $sortConfig)` without return type (`Query/Handler/EAVQueryHandler.php:196`) | `applySort(SortConfig $sortConfig): void` (`V6:Query/Handler/Doctrine/DoctrineQueryHandler.php:120`) |
| calls `AbstractQueryHandler::__construct(...)` directly, skipping the parent (`:64`) | `DoctrineQueryHandler::__construct` takes `ManagerRegistry` and a promoted `protected DoctrineAttributeMetadataResolver` (`V6:...DoctrineQueryHandler.php:47-52`): left uninitialised, used by the inherited `getAttributeMetadata()` (`:109-118`), i.e. by the non-EAV sort fallback `parent::applySort()` (`EAVQueryHandler.php:205-207`) |
| fallback types `Sidus\FilterBundle\Filter\Type\Doctrine\*` (`filter_type.yml:16,26,36,46,58,70`) | they live at `Sidus\FilterBundle\Doctrine\Filter\Type\*` (`V6:Doctrine/Filter/Type/DateRangeFilterType.php:13`): container compile error |
| `new DoctrineORMAdapter($qb, false, $depth)` (`Pager/Adapter/EAVAdapter.php:39`) | third parameter is `?bool $useOutputWalkers` (`V6:Pagination/DoctrineORMAdapter.php:36-40`): the integer depth `2` is coerced to `true` (no `strict_types` in the file). A bug that must not be ported |

So no consumer can currently run `provider: sidus.eav` against 6.0.4. `grep` over `~/www` (excluding `vendor`, `node_modules`, `var/cache`) for `sidus.eav|EAVFilterBundle|eav-filter-bundle|EAVQueryHandler` finds **no consumer** [D]. Whoever has such a config pins a very old stack.

---

### 2. v6 responsibility → v7 mapping

#### 2.1 Provider name, factory, handler (i)

v6 `EAVQueryHandler` overrides, one by one (`Query/Handler/EAVQueryHandler.php`):

| v6 override | v7 home |
|---|---|
| constructor: `entityReference = getFamily()->getDataClass()`, repository (`:64-70`) | list option `family` → root entity = `FamilyInterface::getDataClass()`, resolved by the EAV factory per list (2.2.3 M6) |
| `getFamily()` from option `family` (`:78-88`) | list option `options.family` (required, validated at compile time as a string; existence checked at warm-up) |
| `getQueryBuilder()`: `repository->createQueryBuilder(alias)` + `andWhere("{alias}.family = :uniqid")` when `enforce_family_condition` (`:145-158`) | **root condition** hook `applyRoot()` (M4): `root.family = :p1`, parameter bound **untyped** (see risk R7) |
| `getEAVAttributeQueryBuilder()` / `getEAVAttributes()` / `isEAVFilter()` (`:98-138`) | `EavFieldResolver` (2.2) |
| `applySort()`: EAV attribute → fresh join + `addOrderBy` ASC/DESC, else `parent::applySort` (`:196-215`) | none: the core `Sort` criterion is compiled by the Doctrine `SortCompiler`, over fields resolved by `EavFieldResolver` (it falls back to Doctrine metadata for native fields such as `id`, `updatedAt`) |
| `createPager()` with `EAVAdapter` + context (`:222-235`) | result hydrator hook (M5), 2.3 |
| `getQueryContext()` / `getResultContext()` (`:165-188`) | `EavQueryState` (2.4) |

**Provider name [P]: `sidus_eav`** (v6 `sidus.eav`; v7 names use underscores: `doctrine`, `in_memory`, `elasticsearch`). Factory: `Sidus\EAVFilterBundle\Factory\EavQueryHandlerFactory implements Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface` (CORE:`src/Provider/QueryHandlerFactoryInterface.php:15-27`): static `getProvider()`, static `configureListOptions()`, static `configureFilterOptions()`, `createQueryHandler(ListDefinition, FilterTypeRegistry, ?CountStrategyInterface)`. It must return a new handler on every call. It **composes** the Doctrine handler; it does not subclass it (the handler is `final`, DOCTRINE:`src/Query/DoctrineQueryHandler.php:26`).

**Decision D1 for the owner (the one that shapes the Doctrine bundle changes):**

| | A. own provider `sidus_eav` (recommended) | B. `provider: doctrine` + `options.dialect: sidus_eav` |
|---|---|---|
| list options | validated at compile time by `EavQueryHandlerFactory::configureListOptions()` (`family` required, types) | the Doctrine factory's static `configureListOptions()` (DOCTRINE:`src/Factory/DoctrineQueryHandlerFactory.php:74`) rejects unknown keys; it would need a free-form `dialect_options` map validated only at warm-up |
| `provider_options.sidus_eav.*` | validated at compile time (CORE:`src/Definition/DefinitionResolver.php:111-114` validates `provider_options.<provider>` with the factory of that provider) | nobody validates it at compile time (ignored by the others, design 6) |
| capability table | own `Capabilities('sidus_eav', …)`, may refuse criteria the EAV model cannot honour | the Doctrine one |
| compiler reuse | needs a mechanism: `ListDefinitionPass::capabilities()` maps compilers by their static `getProvider()` (CORE:`src/DependencyInjection/ListDefinitionPass.php:106-122`), and all 14 Doctrine compilers return `'doctrine'` (DOCTRINE:`src/Compiler/AbstractDoctrineCompiler.php:17`, `SortCompiler.php:28`) | free |
| hard-coded `'doctrine'` to make parametric | 11 sites in 7 files (M3) | none |
| `providers: [doctrine]` filters / `Native('doctrine', …)` | **not** active on an EAV list (use `providers: [sidus_eav]`, `Native('sidus_eav', …)`) | stay active (the list *is* a Doctrine list) |
| v6 → v7 config change for users | rename `sidus.eav` → `sidus_eav` | `provider: doctrine` + new keys |

I recommend A (fail-early compile-time validation is a design goal, CORE:`docs/DESIGN.md` §0 goal 4) and the rest of this document assumes it. B is the cheaper fallback if the owner prefers fewer Doctrine-bundle changes; under B, drop M3 and move `family` etc. under `options.dialect_options`.

#### 2.2 Attribute resolution (ii)

##### 2.2.1 What v6 did [D]

`EAVFilterHelper::getEAVAttributeQueryBuilder()` (`:39-67`): for each dot segment, take the attribute of the current family; for the **next** segment, require the attribute's option `allowed_families` to hold **exactly one** family (`:53-58`, `UnexpectedValueException` otherwise), switch to that family and call `$attributeQueryBuilder->join()` (`:60`) which returns a new `EAVQueryBuilder` rooted on a related-Data alias.

`MODEL:Doctrine/AttributeQueryBuilder.php`:
- every `AttributeQueryBuilder` gets its **own** join alias `uniqid('join')` (`:505-509`, `:516-519`) and one `LEFT JOIN root.values <alias> WITH <alias>.attributeCode = :uniq [AND <alias>.familyCode = :uniq] [AND <alias>.<axis> = :uniq]…` (`:389-444`; family part `:405-413` only when `enforce_family_condition`; context axes `:415-437`);
- a relation attribute additionally joins `<alias>.dataValue` (`:446-451`, `join()` `:349-362`);
- column = `<alias>.<attribute type databaseType>` (`:375-378`), e.g. `stringValue` (`MODEL:Resources/config/services/attribute_types.yml:15`), `dataValue` for `data_selector`/`embed` (`:117`, `:127`);
- the "join applied once" guard (`$joinApplied`, `:42`, `:105-107`) comes from `176a8d8` (2017-04-27, "same join could be applied multiple times", MODEL history, quoted in CORE:`docs/DESIGN.md` §1.1); `clone` of a builder sets `skipJoin` (`:476-480`) so that two conditions share one join: used by the date range for *from* and *to* on **the same value row** (`Filter/Type/DateRangeFilterType.php:46`);
- statics `AttributeQueryBuilder::$builtJoins` (`:27`, `:508`) and `EAVQueryBuilder::$queryBuilders` (`MODEL:Doctrine/EAVQueryBuilder.php:24`, `:49`) grow forever (profiler data): a leak in a worker.

A filter with several attributes: one builder per attribute, `getOr(...)` (`Filter/Type/AbstractSimpleFilterType.php:42-49`).
A filter on a non-EAV name (`id`, `updatedAt`): `isEAVFilter()` false when **any** attribute is unknown to the family (`EAVQueryHandler.php:129-138`) → the whole filter goes to the Doctrine fallback type (`AbstractSimpleFilterType.php:30-33`); sort does the same on the first segment (`:203-208`).

##### 2.2.2 v7 semantics and what `EavFieldResolver` must return [P, built on D]

Target semantics (CORE:`docs/DESIGN.md` §8.2): to-one prefixes share one LEFT JOIN; every criterion on a path through a to-many step is its own correlated `EXISTS`; deterministic aliases `p1…`.

Mapping of EAV value storage to Doctrine steps. A path `a₁.a₂…aₙ` is resolved against the list's family `F₀`:

| Segment | Resolved as |
|---|---|
| `aᵢ` is an attribute of `Fᵢ₋₁` (`hasAttribute`) | one **value step**: `source` = current Data class, `name` = `values` (the Doctrine association `AbstractData::$values`, `MODEL:Entity/AbstractData.php:65-69`, inverse side `mappedBy: data`), `target` = value class, `mappedBy` = `data`, `targetIdentifier` = `id`, plus **conditions** `attributeCode = 'aᵢ'` [AND `familyCode = Fᵢ₋₁.code` when `enforce_family`] [AND context axes, 2.4]. `toMany` = `attribute->isCollection()` **or** the attribute is contextual and its context is not statically pinned (see below) |
| `aᵢ` is the last segment and a scalar attribute | leaf = `getType()->getDatabaseType()` on the value class (`stringValue`, `integerValue`, `decimalValue`, `boolValue`, `dateValue`, `datetimeValue`, `textValue`: `MODEL:Entity/AbstractValue.php:88-132`); `leafType` = the DBAL type read from the value class metadata (`string`, `integer`, `float`, `boolean`, `date`, `datetime`, `text`) so that `FieldCapabilities::refusal()` and `ValueBinder` work unchanged |
| `aᵢ` is the last segment and a relation/embed attribute (`databaseType` = `dataValue`) | path ends on the association `dataValue`: one extra to-one step `values → dataValue` with `joinColumnOnRow: true` (so `IsNull`/`IsEmpty` test the foreign key on the value row, no join) |
| `aᵢ` is not the last segment | must be a relation/embed attribute; its `allowed_families` must hold exactly one family `Fᵢ` (same rule as `EAVFilterHelper.php:53-58`, but reported at **warm-up** with the list, filter and attribute, not on a request). Steps: value step, then `dataValue` step (to-one, `target` = `Fᵢ->getDataClass()`), then continue with `Fᵢ` |
| `aᵢ` is **not** an attribute of `Fᵢ₋₁` | a native Doctrine field/association of the current Data class (`id`, `createdAt`, `updatedAt`, `family`): delegated to a plain `FieldResolver` rooted on that Data class and appended. This replaces the per-filter `isEAVFilter()` fallback with a per-segment one, so a filter mixing native and EAV attributes works |

Cardinality rule for `toMany` [P]: `isCollection()` (`MODEL:Model/Attribute.php:312`, multiple ⇒ collection `:294`, `:538-558`) ⇒ to-many ⇒ `EXISTS` per criterion. A non-collection attribute is to-one **if** at most one value row exists per `(data, attribute, pinned context axes)`. For a *contextual* attribute (`getContextMask()` non-empty, `MODEL:Model/Attribute.php:391`) that holds only when every mask axis is pinned to a **scalar**; v6 with no `query_context` joined every context row (`AttributeQueryBuilder.php:415` is skipped, rows multiply). The resolved field is memoised per list and request-agnostic, so the rule must be static: contextual attribute ⇒ to-many unless `options.query_context` pins every mask axis to a scalar **and** `use_global_context` is false. Conservative, always correct, costs only an `EXISTS`. A sort column on a to-many EAV attribute is refused at warm-up without `provider_options.sidus_eav.aggregate: min|max|count` (Doctrine rule, `FieldCapabilities::sortRefusal()`), where v6 silently multiplied rows.

Worked shapes (illustrative DQL; parameter numbering follows join-then-predicate order of `FieldAccess::matching`):

```text
-- title (single-valued string), text contains 'x', sorted by title
SELECT root FROM App\Entity\Data root
LEFT JOIN root.values title_ WITH title_.attributeCode = :p2 AND title_.familyCode = :p3
WHERE root.family = :p1 AND LOWER(title_.stringValue) LIKE :p4 ESCAPE '!'
ORDER BY CASE WHEN title_.stringValue IS NULL THEN 0 ELSE 1 END, title_.stringValue ASC, root.id ASC

-- tags.label (tags: collection of Tag data, label: single-valued), exact 'php'  (independent per criterion)
WHERE root.family = :p1 AND EXISTS (
  SELECT 1 FROM App\Entity\Value x1_values
  LEFT JOIN x1_values.dataValue x1_values_dataValue
  LEFT JOIN x1_values_dataValue.values x1_values_dataValue_values WITH x1_values_dataValue_values.attributeCode = :p4 …
  WHERE x1_values.data = root AND x1_values.attributeCode = :p2 AND x1_values.familyCode = :p3 AND x1_values_dataValue_values.stringValue = :p5)
```

**What `EavFieldResolver` returns:** exactly the existing immutable `ResolvedField` (DOCTRINE:`src/Metadata/ResolvedField.php:13-30`: `field, rootEntity, steps, leaf, leafType, leafNullable, leafEntity`) built from `ResolvedStep`s that additionally carry an `id` and `conditions` (M2). It never touches the request: context values enter through the per-request query state (2.4).

##### 2.2.3 Doctrine bundle: what exists, what is missing

Existing seams, read [D]:

| Seam | Where | Usable by EAV? |
|---|---|---|
| Per-request customizers `withCustomizer(\Closure(DoctrineQueryContext): void)` run on the fresh builder before criteria | DOCTRINE:`src/Query/DoctrineQueryHandler.php:52` (`withCustomizer`), applied in `createContext()` `:60-77` (loop `:72-74`) | yes for a root condition, but only through a handler *instance*; the factory creates it, and the EAV factory cannot intercept it without owning the factory (M4) |
| `DoctrineQueryContext::parameter()` (`p1…`), `queryBuilder()`, `joins()`, `fieldOption()`, `requireDistinct()` | DOCTRINE:`src/Query/DoctrineQueryContext.php:71,47,52,133,83` | yes |
| `JoinRegistry::aliasFor($path)` / `alias($field,$depth)` | `src/Query/JoinRegistry.php:51,65` | alias key is `$step->name` (`:77`) → every EAV step is named `values`: **all attributes would share one join** (M2) |
| Compiler registry by criterion class from tag `sidus_doctrine_filter.compiler` | `src/SidusDoctrineFilterBundle.php:18,27-28`, `config/services.php:43-44`, `src/Compiler/DoctrineCompiler.php:14` | the factory builds `new DoctrineCompiler($compilers)` from **every** tagged compiler (`src/Factory/DoctrineQueryHandlerFactory.php:64`), no filtering by provider: an EAV compiler for `Equals` would silently replace the Doctrine one |
| Named joins `options.joins` (new, uncommitted: `src/Metadata/NamedJoin.php`, `ResolvedStep::$join`) | `FieldResolver::__construct(..., array $joins = [])` takes `NamedJoin`s programmatically | **not enough**: `on` is `{field, equals}` only (no `attributeCode = …`, no family, no context), and `equals` "must be a path of the root entity", never through another named join (`src/Metadata/FieldResolver.php:62`), so `author.name` (value → related data → value) cannot chain |
| `provider_options.doctrine.join: shared` (new) | `DoctrineQueryHandlerFactory::configureFilterOptions()` `:85` | opt-in "same element satisfies all criteria" for collections; EAV inherits it for free once the provider name is parametric |
| `Native('doctrine', \Closure(DoctrineQueryContext): string)` | `src/Compiler/NativeCompiler.php:26` | hard-coded provider name (M3) |

**Missing, to add to the Doctrine bundle [P]** (names are proposals; signatures follow the bundle's existing style):

**M1: `FieldResolverInterface` (field resolution seam).** `FieldResolver` is `final` (`src/Metadata/FieldResolver.php:21`), typed concretely in `ListMapping::$fields` (`src/Metadata/ListMapping.php:21`) and `JoinRegistry` (`src/Query/JoinRegistry.php:38`), and instantiated with `new` in the factory (`src/Factory/DoctrineQueryHandlerFactory.php:139`).
```php
namespace Sidus\DoctrineFilterBundle\Metadata;
interface FieldResolverInterface
{
    public function resolve(Field|string $field): ResolvedField;   // memoising, immutable results, config-error on failure
    public function identifier(): string;                           // tie-breaker / COUNT field of the root
}
// final class FieldResolver implements FieldResolverInterface; ListMapping::$fields and JoinRegistry take the interface.
```
The factory needs a way to obtain a resolver per list: either the extension object of M6, or `FieldResolverFactoryInterface::create(ListDefinition $list): FieldResolverInterface`.

**M2: step identity and join conditions** (the load-bearing change).
```php
namespace Sidus\DoctrineFilterBundle\Metadata;
interface StepConditionInterface
{
    /** A DQL boolean expression on the alias of the step's target. Bind values with $context->parameter(). Called once per join / EXISTS built. */
    public function dql(string $alias, \Sidus\DoctrineFilterBundle\Query\DoctrineQueryContext $context): string;
}
final readonly class ResolvedStep
{
    // existing positional parameters unchanged (src/Metadata/ResolvedStep.php:19-28), appended:
    //   public ?string $id = null,                 // identity of the step in alias keys; defaults to $name
    //   /** @var list<StepConditionInterface> */ public array $conditions = [],
    public function key(): string { return $this->id ?? $this->name; }
}
```
Consumers to change: `JoinRegistry::alias()` key `$key .= … $step->name` (`:77`) → `$step->key()`; `JoinRegistry::join()` `leftJoin($parent.'.'.$step->name, $alias)` (`:112`) → add `Join::WITH` + `implode(' AND ', conditions)`; `generate()` (`:137-139`) must sanitise the key to `[A-Za-z0-9_]` (it only replaces `.` today; attribute codes are not validated by the model: R5); `FieldAccess::subquery()` (`src/Query/FieldAccess.php:93`, alias `$prefix.$step->name` at `:100`, where-clause build `:101-110`, nested joins `:115`) → append the conditions to the `WHERE` of the first step and as `WITH` on nested joins. `JoinRegistry` is constructed as an argument of the context (`DoctrineQueryHandler.php:64-67`), i.e. before it, so it needs the context attached lazily (e.g. `DoctrineQueryContext`'s constructor calls `$joins->attach($this)`), to pass it to `dql()`.

**M3: provider name becomes a parameter, and a provider may reuse another's compilers.** Hard-coded `'doctrine'` (11 sites, last read): `DoctrineQueryHandlerFactory::PROVIDER` (`:44`), `getProvider()` (`:69`), `fieldOptions()` (`:163`); `AbstractDoctrineCompiler::getProvider()` (`:17`); `SortCompiler::getProvider()` (`:28`) and its `provider_options` lookup (`:41`); `NativeCompiler` (`:26`); `ListWarmer` (`:36`); `LintCommand` (`:49`); `ListValidator` (`:108`, `:121`, `:136`). Proposed:
- `DoctrineQueryContext::provider(): string` (set by the factory), used by `SortCompiler` (aggregate lookup) and `NativeCompiler` (accept `Native($context->provider(), …)`), and `DoctrineQueryHandlerFactory::fieldOptions()` reads `provider_options.<provider>`;
- compiler reuse: **core** gets one optional static, `Provider\ReusesCompilers::getCompilerProviders(): list<string>` implemented by `EavQueryHandlerFactory` (returns `['doctrine']`), honoured by `ListDefinitionPass::capabilities()` (`:106-122`: today `if (isset($compilers[$class::getProvider()]))`); at runtime the Doctrine factory builds its `DoctrineCompiler` from the compilers whose provider ∈ its compiler providers. *Fallback without a core change*: make the 14 compilers non-final and ship 14 one-line subclasses in the EAV bundle overriding `getProvider()` (and have the factory filter compilers by provider, which it does not do today). I prefer the core change.
- `ListWarmer`/`LintCommand`/`ListValidator` validate every list whose provider is registered as a Doctrine-family provider (a `DoctrineProviderRegistry` or the factories themselves), not only `'doctrine'`.

**M4: root condition hook.**
```php
namespace Sidus\DoctrineFilterBundle\Extension;
interface ListExtensionInterface   // one per dialect, found by the factory
{
    public function rootEntity(ListDefinition $list): string;                          // EAV: Family::getDataClass()
    public function createFieldResolver(ListDefinition $list): FieldResolverInterface; // M1
    public function applyRoot(DoctrineQueryContext $context, ListDefinition $list): void; // EAV: root.family = :p1; runs BEFORE user customizers and criteria
    public function hydrator(ListDefinition $list): ?ResultHydratorInterface;          // M5
    /** @return list<string> problems found at warm-up / lint:sidus-filter (M8) */
    public function validate(ListDefinition $list): array;
}
```
`DoctrineQueryHandler::createContext()` (`:60-77`) calls `applyRoot()` right after creating the builder (`:63`) and before the customizers loop (`:72-74`).

**M5: result hydrator hook.**
```php
namespace Sidus\DoctrineFilterBundle\Extension;
interface ResultHydratorInterface
{
    /** Called once per handle(), with the fetched page (for count: none it includes the one look-ahead item, CORE:src/Provider/AbstractQueryHandler.php:64-66). Returns the items to expose. */
    public function hydrate(array $items, DoctrineQueryContext $context): array;
}
```
Called from `DoctrineQueryHandler::fetch()` (`:91-104`) on both return paths (`:100` Paginator, `:103` `getResult()`). `fetch()` is the only post-query point because `AbstractQueryHandler::handle()` is `final` (CORE:`src/Provider/AbstractQueryHandler.php:44`).

**M6: composition instead of `final`.** `DoctrineQueryHandlerFactory` (`:42`) and `DoctrineQueryHandler` (`:26`) stay `final`; the EAV bundle gets its own factory class `EavQueryHandlerFactory` that **delegates** to an internal `DoctrineHandlerBuilder` (extracted from `createQueryHandler()` `:92-110` + `mapping()` `:132-157`), parameterised by `(provider name, ListExtensionInterface)`. i.e. the Doctrine bundle exposes the *builder*, the EAV factory owns provider name, list options and filter options.

**M7: per-request extension slot.** `DoctrineQueryContext` gets `setExtension(object $state)` / `extension(string $class): ?object` (typed bag; the context is per request and never a service). EAV stores `EavQueryState` (2.4) there in `applyRoot()`.

**M8: validation of extension lists.** `ListValidator` takes the mapping from `DoctrineQueryHandlerFactory::mapping()` (`src/Validation/ListValidator.php:52`), which would now be the builder's; plus an extension validation hook `ListExtensionInterface::validate(ListDefinition): list<string>` (EAV: family exists, attributes resolve, `allowed_families`, context axes valid) merged into the same report (`ListWarmer` fails `cache:warmup`, `lint:sidus-filter`).

Everything else the EAV bundle needs exists: `ValueBinder` (typed binding by `leafType`), `FieldCapabilities` (field-dependent refusals), `SortCompiler` (nulls first/last, identifier tie-break), `IsNull`/`IsEmpty` compilers (via the `ResolvedField` foreign-key path), `FieldAccess::matching()` (EXISTS), count (`countNative()` `:106-116`), `fieldOption()`.

#### 2.3 Hydrating data loader → hook on the result items (iii)

v6 [D]: `EAVAdapter::getSlice()` loads the page through the base adapter then `dataLoader->load($iterator, $depth)` (`Pager/Adapter/EAVAdapter.php:61-67`); the handler first calls `dataLoader->setCurrentContext($resultContext)` when `result_context` is set (`EAVQueryHandler.php:224-226`) and passes `loader_depth` (default 2, `:232`). `OptimizedDataLoader::load()` (`MODEL:Doctrine/OptimizedDataLoader.php:61-89`) loads **all values of the page in one query per value class** (`getValues()` `:165-177`, `IN (:data)`) then related entities (`autoload` option or embedded attributes, `:213-237`) for `depth` levels.

v7 [P]: `Sidus\EAVFilterBundle\Hydrator\DataLoaderHydrator implements ResultHydratorInterface` (M5), a shared immutable service holding `DataLoaderInterface`:
```php
public function hydrate(array $items, DoctrineQueryContext $context): array
{
    $state = $context->extension(EavQueryState::class);
    foreach ($items as $item) {
        if (null !== $state->resultContext && $item instanceof ContextualDataInterface) {
            $item->setCurrentContext($state->resultContext);          // on the entity, never on the shared loader
        }
    }
    $this->dataLoader->load($items, $state->loaderDepth);              // 1 query / value class + related depth
    return $items;
}
```
Why not call `setCurrentContext()` on the loader as v6 did [D]: `OptimizedDataLoader` is a shared service (`MODEL:Resources/config/services/entities.yml:5-11`, no `kernel.reset`) with `$currentContext` (`:33`) and `$loadedEntityIds` (`:36`): `load()` skips every entity id already seen (`:72`) and overrides entity contexts with its own stale `$currentContext` (`:78-80`). In a worker the second request would skip hydration of entities that are new objects with the same ids (N+1 lazy loading) and could carry the previous list's context. **Prerequisite P-MODEL-1** (model bundle, not this one): make the loader `ResetInterface` (`kernel.reset`, clear both properties) or stateless; until then the hydrator is only correct in php-fpm. With `count: none` the hook gets `per_page + 1` items; the extra one is loaded then discarded (negligible).

`loader_depth` → `options.loader_depth` (int ≥ 0, default 2). `EAVAdapter`, `DataLoaderInterface` wiring in the factory, and `Pager/` are deleted (the Pagerfanta bridge is `Sidus\FilterBundle\Pagerfanta\ListResultAdapter::createPager($result)`, already hydrated).

#### 2.4 Query / result contexts (iv)

v6 [D]: `query_context` (axis → value or list) filters value joins (`AttributeQueryBuilder.php:415-437`): axis must be in `$valueClass::getContextKeys()` else `ContextException` (`:421-424`), axes missing from the attribute's `getContextMask()` are skipped (`:425-427`), array value → `IN`, scalar → `=`; `ContextualValueInterface` required (`:418-420`). `getQueryContext()` merges the family's global context **only when `query_context` is truthy and `use_global_context` is true** (`EAVQueryHandler.php:167-170`): `use_global_context: true` alone does nothing. `result_context` is merged over the global context (`:180-188`) and only set on the loader when non-null.

v7 [P]: options `query_context`, `use_global_context`, `result_context` keep their names and types (`array<string, scalar|list<scalar>>`, bool, array). The **effective** contexts are computed per request in `applyRoot()` (the global one is `FamilyInterface::getContext()` → `ContextManager`, `MODEL:Model/Family.php:478-485`, request dependent, therefore never cached in the factory or the definition) and stored in `EavQueryState { ?array $queryContext; ?array $resultContext; int $loaderDepth }` (M7). A `ContextAxisCondition implements StepConditionInterface` on each contextual value step reads `EavQueryState` and emits `alias.axis = :pN` / `IN (:pN)`. Compile/warm-up validation (new, was a request-time `ContextException`): static `query_context` axes ∈ `getContextKeys()` of the value class; `use_global_context: true` without `query_context` is **refused** (it was a silent no-op in v6, open question O6).

#### 2.5 Filter types shipped by v6 → core types + criteria

All of them are provider-independent in v7 (CORE:`docs/DESIGN.md` §5): the per-provider override matrix (`text` for `sidus.eav` vs `text` for `doctrine`) and `setFallbackFilterType` disappear. A second service with an existing type name is a compile-time error (CORE:`src/DependencyInjection/ListDefinitionPass.php:73-75`), so the EAV bundle **cannot** re-register `text`, `choice`…; new types get an `eav_` prefix.

| v6 EAV type (service) | v6 behaviour [D] | v7 | Notes |
|---|---|---|---|
| `text` (`Filter/Type/TextFilterType.php:22`) | `like('%'.trim($data,'%').'%')` | core `text` (default operator `contains`) | wildcards in input now literal; case-insensitive via `LOWER()` instead of DB collation |
| `exact` (`ExactFilterType.php:22`) | `equals($data)` | core `exact` → `Equals` | exactness is now byte-wise on every platform (DOCTRINE `DoctrineQueryContext::exact()`) |
| `advanced_text` (`AdvancedTextFilterType.php:25-50`) | combo option codes | core `text` + `options.operators` | `exact`→`equals`; `like_`→`starts_with`; `_like`→`ends_with`; `_like_`→`contains`; `notlike_`→`not_starts_with`; `_notlike`→`not_ends_with`; `_notlike_`→`not_contains`; `empty` (`= ''`)→`is_empty`; `notempty` (`!= ''`)→`is_not_empty`; `null`→`is_null`; `notnull`→`is_not_null` |
| `advanced_number` (`AdvancedNumberFilterType.php:25-46`) | `exact`, `greaterthan`, `lowerthan`, `greaterthanequals`, `lowerthanequals`, `empty`, `notempty`, `null`, `notnull` | core `number` + `options.operators` | `exact`→`eq`; `greaterthan`→`gt`; `lowerthan`→`lt`; `greaterthanequals`→`gte`; `lowerthanequals`→`lte`; `null`→`is_null`; `notnull`→`is_not_null`; **`empty`/`notempty` have no number equivalent** (they compared an integer column to `''`): map to `is_null`/`is_not_null` after checking no data relied on it |
| `date_range` (`DateRangeFilterType.php:40-51`) | `gte start` AND `lte end` on one value row | core `date_range` → one `Range(min,max)` → one predicate (one EXISTS on a collection) | v6 `lte '2026-01-31'` excluded the last day of a datetime attribute; v7 `to` = end of that day |
| `choice` (`ChoiceFilterType.php:19-72`) | choices **from the attribute's `formOptions['choices']`** merged over attributes (`:32-37`), `choice_translation_domain` (`:45-47`), `in()` / `equals()` | core `choice` when `choices` are configured; **`eav_choice`** when they come from the attribute | `eav_choice` [P]: options `multiple`; instance half reads `FamilyRegistry` at request time (allowed: the instance half may use services, CORE:`docs/DESIGN.md` §18), memoises choices per filter definition (immutable data), emits `Equals`/`In`; static `getRequiredOperations()` = `[Equals]` or `[In]`. Its form mapper (`FormFilterMapperInterface`, tag `sidus_filter.form_mapper`, CORE:`src/Form/FormFilterMapperInterface.php:17`) sets `choices` and `choice_translation_domain` on `ChoiceType` |
| `autocomplete_data` (`AutocompleteDataFilterType.php:19-43`) | autocomplete over related data; `attribute` form option; one attribute only | **`eav_data`** [P] (UI-agnostic name; v7 types know no widgets) | input = identifier(s) (ids), `options.multiple`; emits `In(<path>.id, ids)` over the resolver (`category` → value step + `dataValue` step, leaf `id` of the related Data: the same shape as `relation` in the Doctrine bundle, which does not exist yet, so the EAV bundle ships its own); its form mapper builds `AutocompleteDataSelectorType` with `attribute`, converting entities ↔ ids (`toRaw`/`toFormData`, CORE:`FormFilterMapperInterface.php:29-33`). The one-attribute limit stays (form option needs one attribute) |
| `AbstractEAVFilterType::getDefaultFormOptions()` (`:58-77`): label = `(string) $attribute`, `translate_label=false`, `translation_domain` of the attribute (only when the filter has exactly one attribute) | label from the EAV model | **Form type extension on `Sidus\FilterBundle\Form\Type\ListFormType`** [P] (`getExtendedTypes()`; the class is `final` but extendable by type extension): `finishView()` overrides `label` / `translate_label` / `translation_domain` of each `filters.<name>` child when `definition->provider === 'sidus_eav'`, the filter has one attribute and no `form.label`. Needed because the mappers only see a `FilterDefinition` with no list or provider (CORE:`src/Form/Mapper/AbstractFormFilterMapper.php`, `FormFilterMapperInterface::add()`), and `ListFormFactory` is `final readonly` (`src/Form/ListFormFactory.php:26`, cannot be decorated by subclassing). `translate_label` comes from `MODEL:Form/Extension/LabelTranslationExtension.php` |
| `ComboFilterType` options (`filter_type.yml:55-56,67-68`, `%sidus.filter.advanced_*_choices%`) | combo select | CORE `OperatorValueType` and `sidus.filter.operator.<operator>` keys | nothing to ship |
| `not_null` etc. (not shipped here) | – | core `null`, `not_null`, `not_empty`, `regexp`, `native` work on EAV attributes unchanged | |

Types **not** needed any more: `AbstractEAVFilterType`, `AbstractSimpleFilterType`, `EAVFilterHelper` (the resolver replaces it).

#### 2.6 Configuration: v6 keys → v7

| v6 | v7 |
|---|---|
| `sidus_filter.configurations.<code>` (`README.md:50-51`) | `sidus_filter.lists.<code>` |
| `provider: sidus.eav` | `provider: sidus_eav` |
| `options.family` (required) | `options.family` |
| `options.enforce_family_condition` (default true; also drops the *value* family condition, `EAVQueryHandler.php:106,149`) | `options.enforce_family` (default true) |
| `options.loader_depth` | `options.loader_depth` |
| `options.query_context`, `use_global_context`, `result_context` | same names under `options` |
| `results_per_page` | `pagination.per_page` / `max_per_page` |
| `sortable: [id, label, publicationDate…]` + `default_sort` (`README.md:56-63`) | one `type: sort` filter, `options.columns` (public key → attribute path or native field) + `options.default` |
| `filters.<code>: ~` (type defaults to `text`: `V6:DependencyInjection/Configuration.php:93`) | `type` is **required** |
| `type: autocomplete_data` / `choice` (attribute choices) / `advanced_text` / `advanced_number` | `eav_data` / `eav_choice` / `text` + operators / `number` + operators |
| `label`, `form_type`, `form_options` | `form: { label, type, options }` |
| attribute option `allowed_families` (model config) | unchanged, still the way to nest; **exactly one** family per nested attribute (now a warm-up error) |
| *(new)* `filters.<k>.provider_options.sidus_eav` | `case_insensitive` (`lower`/`ilike`), `join` (`exists`/`shared`) inherited from the Doctrine options; plus [P] `families: { <attribute>: <FamilyCode> }` to disambiguate an attribute with several `allowed_families` (v6 threw, `EAVFilterHelper.php:54-58`); sort column: `aggregate` |

Proposed v7 config for an EAV list [P]:

```yaml
sidus_filter:
    lists:
        news:
            provider: sidus_eav
            options:
                family: News                     # required
                enforce_family: true             # v6 enforce_family_condition
                loader_depth: 2
                query_context: { locale: fr }    # pinned scalar: contextual attributes become single-valued joins
                use_global_context: false
                result_context: { locale: fr }
            pagination: { per_page: 20, max_per_page: 100 }
            count: auto                          # COUNT(root.id); `none` for huge tables
            filters:
                label:             { type: text, attributes: [label], options: { operators: [contains, starts_with] } }
                publicationDate:   { type: date_range }
                publicationStatus: { type: eav_choice }                     # choices come from the attribute
                category:          { type: eav_data, options: { multiple: true } }
                category_label:    { type: text, attributes: [category.label] }   # nested through allowed_families: [Category]
                tag:               { type: text, attributes: [tags.label] }       # to-many: one EXISTS per criterion
                updatedAt:         { type: date_range, attributes: [updatedAt] }  # native field of the Data entity
                sort:
                    type: sort
                    options:
                        columns:
                            id: id
                            label: label
                            publicationDate: publicationDate
                            publicationStatus: publicationStatus
                            updatedAt: updatedAt
                            tags: { field: tags, provider_options: { sidus_eav: { aggregate: count } } }
                        default: [{ column: publicationDate, direction: desc }]
```
Attribute strings are parsed by `Definition\Field` (`[A-Za-z_][A-Za-z0-9_]*` per segment, CORE:`src/Definition/Field.php:27`).

---

### 3. Pager and count (3)

v6 [D]: `Pagerfanta(EAVAdapter)` (`EAVQueryHandler.php:228-234`); count = `count($paginator)` of `DoctrineORMAdapter::getNbResults()` (`V6:Pagination/DoctrineORMAdapter.php:60-63`) with `fetchJoinCollection = false` (`EAVAdapter.php:39`) over a query whose fresh LEFT JOINs multiply rows on to-many attributes [I: Doctrine Paginator behaviour, not executed]. `EAVAdapter` does **not** implement `ManualCountAdapterInterface` (`:15`), so `setNbResults()` never reached the EAV pager.

v7: nothing EAV-specific.
- `count: auto` = `SELECT COUNT(root.id)` on a clone without ORDER BY (DOCTRINE:`DoctrineQueryHandler.php:104-114`), no `DISTINCT` (EXISTS multiplies nothing, root condition is a plain `WHERE`), parameters `p1…` copied with the clone (the `family` parameter included). `DISTINCT`/Paginator mode only appears if the owner opts into `provider_options.sidus_eav.join: shared` or a customizer calls `requireDistinct()`.
- `count: none` or a `CountStrategyInterface` service replace v6's `setNbResults` tricks; `ListInput::withTotalCount()` per request.
- Pager: `Sidus\FilterBundle\Pagerfanta\ListResultAdapter::createPager($result)` (already on the right page, items already hydrated by M5).

---

### 4. Package, tests, plan (4)

#### 4.1 Package layout [P]

`composer.json`: name `sidus/eav-filter-bundle` `^3.0`; `php >=8.4`; `sidus/filter-bundle ^7.0`; `sidus/doctrine-filter-bundle ^<version shipping M1–M8, TBD>`; `sidus/eav-model-bundle ^<ported version, TBD>`; suggest `sidus/eav-bootstrap-bundle` (autocomplete widget), `symfony/form`. License: the old repo says `GPL-2.0` (`composer.json:13`), the model bundle and the siblings MIT (`MODEL:composer.json:11`): the owner must confirm (O8).

```
SidusEAVFilterBundle/
  composer.json, README.md, UPGRADE-3.0.md, CHANGELOG.md
  config/services.php                      # AbstractBundle, like the sibling bundles
  src/SidusEAVFilterBundle.php             # AbstractBundle; autoconfigure ListExtensionInterface, form mappers
  src/Factory/EavQueryHandlerFactory.php   # provider sidus_eav; static configureListOptions (family, enforce_family, loader_depth, query_context, use_global_context, result_context) / configureFilterOptions
  src/Extension/EavListExtension.php       # M4: rootEntity, createFieldResolver, applyRoot (family + EavQueryState), hydrator, validate
  src/Metadata/EavFieldResolver.php        # M1; delegates native segments to Doctrine's FieldResolver
  src/Metadata/ValueColumns.php            # attribute type databaseType -> value column + DBAL type (read from value class metadata)
  src/Metadata/AttributeCondition.php      # StepConditionInterface: attributeCode (+ familyCode)
  src/Metadata/ContextAxisCondition.php    # StepConditionInterface: reads EavQueryState
  src/Query/EavQueryState.php              # effective query/result context + loader depth (per request, in the context slot M7)
  src/Hydrator/DataLoaderHydrator.php      # M5
  src/Filter/Type/EavChoiceFilterType.php  # eav_choice
  src/Filter/Type/EavDataFilterType.php    # eav_data
  src/Form/Mapper/{EavChoiceMapper,EavDataMapper}.php
  src/Form/Extension/ListFormTypeExtension.php   # attribute labels
  src/Validation/EavListValidator.php      # warm-up / lint: family, attributes, allowed_families, axes, sort aggregates
  tests/
```
Namespace stays `Sidus\EAVFilterBundle\` (v6 `composer.json` psr-4 root `""`; 3.0 moves to `src/`). Tags: reuse `sidus_filter.provider_factory`, `sidus_filter.filter_type`, `sidus_filter.form_mapper`, `sidus_filter.criterion_compiler` (core autoconfigure) and the Doctrine bundle's tag for the extension (name TBD in M4).

Files that become **deletable** (all of the repo): every file listed in 1.1, `Resources/config/services/{factory,filter_type,helper}.yml`, the `QueryHandlerFactoryInterface` alias, `Pager/`, `Query/Handler/`, `Filter/EAVFilterHelper.php`, `Filter/Type/Abstract*`, the 5 simple types, `DependencyInjection/SidusEAVFilterExtension.php` (no more `sidus/base-bundle` dependency). What **stays** (as behaviour, not files): family root condition, attribute-code resolution through `allowed_families`, value-table conditions (attribute, family, context), result hydration with `loader_depth`, `query_context` / `result_context`, `enforce_family_condition`, attribute-derived choices and labels.

#### 4.2 Tests to write [P]

*Contract suite (CORE:`src/Test/Contract`, DOCTRINE:`tests/Contract/AbstractDoctrineContractTestCase.php` is the template).* `providerName()` = `sidus_eav`, `listOptions()` = `['family' => 'Post']`, fixtures: families `Post` (title string, status choice, views integer, publishedAt **datetime**, summary text, author data_selector → `allowed_families: [Author]`, tags collection data_selector → `[Tag]`), `Author` (name), `Tag` (label); insert Authors and Tags first, then Posts in fixture order, and map Data id → fixture id in `itemId()` (all three families share one `Data` table so ids interleave; relative order is preserved, so the `…ties_by_id` cases hold). Subset it can pass: **all** of `ContractCases` with the same `knownDivergences()` as the Doctrine SQLite test (ASCII-only `LOWER()`), because every case maps to a resolvable path: `author.name`, `tags.label`, `tags` (`IsEmpty` through the foreign-key path), `author` (`IsNull` → `dataValue IS NULL`, absent row ⇒ NULL), `id` (native), nulls = missing value row **and** a row with a null column (fixture both), `date` attributes would diverge on instants (use `datetime`). Run it also with a second kernel config that has other families in the table (root-condition isolation).

*EAV-specific:*
1. **Resolver** (pure, memoised): code → steps/conditions/leaf/type for every attribute type of `attribute_types.yml`; nested `allowed_families`; native fallback per segment (`createdAt`, `id`); errors: unknown attribute, scalar in the middle, `allowed_families` ≠ 1 (message names list/filter/attribute), relation without target, contextual rules (2.2.2).
2. **Query shape**: single-valued attribute = one shared `LEFT JOIN … WITH` for two filters + the sort; collection = one `EXISTS` per criterion, two criteria independent (the `176a8d8` regression); date range on a collection = one `EXISTS` (the `clone` behaviour); identical DQL for identical shapes, different values same DQL; `p1` = family code; no `uniqid`.
3. **Root condition**: other families' data never returned; `enforce_family: false` multi-family list (`fb60a43` regression) drops both the root and the value-family conditions.
4. **Contexts**: pinned scalar → to-one join with `axis = :p`; list → `IN`; unpinned/global → EXISTS; axis not in the mask skipped; invalid axis fails warm-up; `use_global_context` alone refused; result context set on the entities, **not** on the loader.
5. **Hydrator**: N-query ceiling for a 20-item page at depth 0/1/2 (count queries through a logging middleware, as `tests/Support/RecordingLogger.php` of the Doctrine bundle does); two handlers with different result contexts in one kernel do not leak; two sequential requests re-hydrate new entity objects with the same ids (fails today with the stock loader: documents P-MODEL-1).
6. **Count**: `COUNT(root.id)`, no `DISTINCT`, no ORDER BY, family parameter present.
7. **Filter types/forms**: `eav_choice` choices and translation domain from the attribute; `eav_data` ids normalisation, `In(category.id, …)`; label extension with 1 vs several attributes and explicit `form.label`.
8. **Warm-up/lint**: unknown family, Range on a string attribute (capability refusal), sort on a collection without aggregate.
9. **Container**: compiles with zero deprecations on Symfony 8.1; `provider_options.sidus_eav` typos fail at compile time.
10. **Regression ports from v6 history**: `fb60a43` (multi-family), `a5b9e30` (contexts), `a24f475`/`25a9e04` (advanced filters with no data, inverted condition: the `empty` / `notempty` cases).

#### 4.3 Dependency-ordered plan

1. **Owner decision D1** (A vs B) and O1–O3 below; fixes the shape of everything else.
2. **P-MODEL**: port `sidus/eav-model-bundle` to PHP 8.4 / Symfony 8.1 / ORM 3 / DBAL 4 (attribute mapping, `Doctrine\Persistence`, `FamilyType` without the platform event manager (`MODEL:Doctrine/Types/FamilyType.php:41`), `DoctrineMetadataListener` on ORM 3 mapping objects (`MODEL:Event/DoctrineMetadataListener.php:51-63` writes `associationMappings[...]['targetEntity']`), loader reset P-MODEL-1). Independent of the filter work, blocking for the EAV tests.
3. **Core** (only under D1-A): optional `ReusesCompilers` + `ListDefinitionPass::capabilities()` tweak (or the 14-subclass fallback).
4. **Doctrine bundle**: M1, M2 (+ tests in `tests/Query/QueryShapeTest.php` style), M3, M4–M8, using a throw-away dialect in its own tests so the seams are covered without EAV.
5. **EAV bundle skeleton**: `composer.json`, `AbstractBundle`, factory with static option resolvers, `EavQueryState`.
6. `EavFieldResolver` + `EavListValidator` (2.2.2) with unit tests, then root condition + context conditions.
7. `DataLoaderHydrator`.
8. `eav_choice`, `eav_data`, form mappers, label extension.
9. Contract + EAV tests (4.2) on SQLite, then PostgreSQL.
10. `README.md`, `UPGRADE-3.0.md` (tables 2.5/2.6), `CHANGELOG.md`; delete the v6 files (clean cutover, no shims).

---

### 5. Behaviour changes, risks, open questions

**Behaviour changes** (all [D] from the cited v6 code vs the v7 reference):

| # | v6 | v7 |
|---|---|---|
| 1 | `notlike` on an entity with no value row: `NULL NOT LIKE` is unknown, entity excluded (LEFT JOIN; `MODEL:Doctrine/AttributeQueryBuilder.php:201-204`, `AdvancedTextFilterType.php:34-39`) | `not_contains` is `Not(Contains)`, **true for null**: entity included |
| 2 | `%` / `_` inside input are wildcards (only outer `%` trimmed, `AdvancedTextFilterType.php:29-39`) | literal |
| 3 | `empty` = `= ''` (null excluded, `AdvancedTextFilterType.php:40-41`) | `is_empty` = null **or** `''` |
| 4 | date range end bound `<= '2026-01-31'` | end of day |
| 5 | fresh join per condition; to-many sort multiplies rows; page `LIMIT` over multiplied rows [I] | collection: EXISTS per criterion; sort needs an aggregate |
| 6 | count = Paginator count over joined rows | `COUNT(root.id)` |
| 7 | aliases/params `uniqid` (query-cache miss) | deterministic `root`, `p1…` (root alias was `e`: customizer code using `e.` breaks) |
| 8 | contextual attribute with no `query_context`: all context rows joined | EXISTS (any row), no duplicates |
| 9 | `use_global_context` ignored without `query_context` (`EAVQueryHandler.php:167-170`) | refused at warm-up (O6) |
| 10 | nested attribute with ≠ 1 `allowed_families`: exception on the request (`EAVFilterHelper.php:54-58`) | warm-up error, optional `provider_options.sidus_eav.families` |
| 11 | one unknown attribute sends the **whole filter** to the Doctrine fallback (`EAVQueryHandler.php:129-138`) | per-segment resolution: mixed native/EAV attributes work |

**Risks**
- **R1 [D]** M1–M8 do not exist; the Doctrine bundle is still moving (its working tree changed during this analysis). Coordinate the signatures with its author before writing EAV code.
- **R2 [D]** Model bundle not ported (headline 4, plan step 2). Also `FamilyType::convertToDatabaseValue()` accepts only a `FamilyInterface` (`MODEL:Doctrine/Types/FamilyType.php:59-61`): the root condition must bind the family **code untyped** (as v6 did, `EAVQueryHandler.php:153`), and a filter on the native `family` field with a string value would throw (document: do not expose it, or give it a custom type).
- **R3 [D]** Shared worker state in the model bundle (loader, static registries): 2.3.
- **R4 [I]** Single-valued join sharing assumes at most one value row per `(data, attribute, pinned context)`. v6 never relied on it (fresh join per condition). If the data can hold duplicates the shared LEFT JOIN multiplies roots (short pages, wrong count). Options: unique index on `(data_id, attribute_code, <context axes>)`, or treat every EAV attribute as to-many (all sorts then need an aggregate).
- **R5 [D]** `Field` accepts only `[A-Za-z_][A-Za-z0-9_]*` segments (CORE:`src/Definition/Field.php:27`); the model validates nothing about attribute codes (no regex in `Model/Attribute.php`, `DependencyInjection/Configuration.php`, `Registry/*`): a code with a hyphen cannot be used in a filter attribute. Check the codes in use.
- **R6 [I]** Families with a different `value_class` (`Family::getValueClass()`, `MODEL:Model/Family.php:377`) while `Data::$values` has a single mapped target (`DoctrineMetadataListener`, one value class `:BASE_VALUE_CLASS`): the value step's `target` is read from the association metadata; `leafType` should be read from the family's own value class. Not verified.
- **R7 [D]** `sidus/eav-bootstrap-bundle` (`AutocompleteDataSelectorType`, its `attribute` option) is not available here: the `eav_data` mapper is written against the call site in `AutocompleteDataFilterType.php:36-42` only.
- **R8 [D]** Contract suite on EAV needs a per-case id mapping (interleaved Data ids).

**Open questions for the owner**
- **O1** D1: A (provider `sidus_eav`, core `ReusesCompilers`, 11 hard-coded provider names made parametric) or B (`provider: doctrine` + dialect)?
- **O2** Who ports `sidus/eav-model-bundle`, to which version, and is `kernel.reset` on `OptimizedDataLoader` acceptable there (P-MODEL-1)?
- **O3** M1–M8: accepted into the Doctrine bundle as proposed (names, `Extension\` namespace, a `ListExtensionInterface` vs three separate interfaces)?
- **O4** Include sub-families? v6 filtered `family = <code>` exactly (`EAVQueryHandler.php:152`) although `FamilyInterface::getMatchingCodes()` exists (`MODEL:Model/Family.php:363`). Keep exact (this document) or add `options.include_subfamilies` → `root.family IN (:p1)`?
- **O5** Contextual attributes with `use_global_context: true`: the conservative rule makes them to-many, so sorting on them needs an aggregate. Accept, or add `provider_options.sidus_eav.pinned_context: true` on a sort column to assert a scalar global context?
- **O6** Refuse `use_global_context` without `query_context`, or give it the evident meaning (apply the global context)?
- **O7** Type names `eav_choice` / `eav_data`, and one `eav_data` per attribute (autocomplete limit) acceptable?
- **O8** Licence of 3.0 (old `GPL-2.0`, siblings MIT).
- **O9** Mixed lists: is `enforce_family: false` with attributes defined on several families (same `attributeCode`, different `familyCode`) still required? It drops the family condition on the value join too (`AttributeQueryBuilder.php:405`); v7 keeps the same meaning.

---

## (d) keres-platform and qk-sds-api

Both repos were read only. Paths: keres-platform `/home/vincent/www/keres-platform`, qk-sds-api `/home/vincent/www/archives/qk-sds-api` (abbreviated `qk:` below; `qk:src/` = `src/Quarks/SdsAnalysis/`... where a path starts with `SdsAnalysis/` it is relative to `qk:src/Quarks/`). Doctrine bundle = `/home/vincent/www/sidus-bundles/SidusDoctrineFilterBundle/src` (`dfb:`), read at the state of 2026-10-20; everything about it is "as built today" and may move.

Both apps consume the filter bundle **only through sidus/datagrid-bundle + sidus/admin-bundle** (list YAML under `sidus_data_grid`, `DataGridHelper`, `query_handler` blocks). The DataGrid/Admin side of the migration (Twig `datagrid.queryHandler.*`, `buildDataGridForm`, `handleRequest`) is covered by the DataGrid section; this section only covers what is filter-specific and flags the coupling points.

### 0. What the Doctrine bundle can and cannot do today (read in `dfb:`)

| Need | State | Evidence |
|---|---|---|
| to-one shared LEFT JOIN, to-many correlated EXISTS per criterion, `AnyOf` = `OR` | **exists** | `dfb:Query/JoinRegistry.php:13-22`, `dfb:Query/FieldAccess.php:11-22`, `dfb:Compiler/AnyOfCompiler.php:19-24` |
| `Contains`/`StartsWith`/`EndsWith`: case-insensitive = `LOWER(col) LIKE :p ESCAPE '!'` with needle lower-cased by PHP, or `ILIKE` (PostgreSQL) | **exists** | `dfb:Compiler/AbstractTextCompiler.php:36-52` |
| `Matches` (regexp) | **exists**, DQL functions `SIDUS_REGEXP`/`SIDUS_IREGEXP` are **registered by the bundle itself** when a handler is created, nothing to declare in the app | `dfb:Compiler/MatchesCompiler.php:28-34`, `dfb:Dql/DqlFunctions.php:17-33`, `dfb:Factory/DoctrineQueryHandlerFactory.php` (`DqlFunctions::register(...)` in `createQueryHandler`) |
| `Native('doctrine', Closure(DoctrineQueryContext): string)` | **exists** (payload must be a `\Closure` returning a DQL condition; `$context->parameter($value, $type)` binds) | `dfb:Compiler/NativeCompiler.php:21-32`, `dfb:Query/DoctrineQueryContext.php` (`parameter()`) |
| Per-request customizer, `requireDistinct()` (COUNT DISTINCT + ORM Paginator) | **exists** | `dfb:Query/DoctrineQueryHandler.php:38-58,81-90`, `DoctrineQueryContext.php` (`requireDistinct`) |
| Sort: to-one/scalar columns, nulls first asc / last desc, id tie-breaker, to-many through `aggregate: count\|min\|max`, `binary_sort` | **exists** | `dfb:Compiler/SortCompiler.php:19-66` |
| Platforms | PostgreSQL and SQLite only (anything else: `ConfigurationException`) | `dfb:Platform/Dialect.php:20-26` |
| **Named joins** `options.joins` (`on`, `cardinality`, `shared`) | **NOT implemented.** `joins` is accepted as an opaque array (`'joins' => []`, `dfb:Factory/DoctrineQueryHandlerFactory.php:67`) and no other file reads it (grep `joins` over `dfb:src`: only `JoinRegistry`/`FieldAccess` internals) | |
| **JSON paths / casts** `{path, json, cast}` | **NOT implemented.** `FieldCapabilities` refuses any criterion and any sort on a JSON field: `'JSON paths are not available'` (`dfb:Metadata/FieldCapabilities.php:36-37,63-64`); `FieldAccess::column()` only emits `alias.leaf` (`dfb:Query/FieldAccess.php:74-82`); no `JsonPathCompilerInterface` exists in `dfb:src` | |
| **Expression sort** `provider_options.doctrine.expression` | **NOT implemented.** `SortCompiler` reads only `provider_options.doctrine.aggregate` (`dfb:Compiler/SortCompiler.php:35`) | |
| `IsNull`/`IsEmpty`/`Equals`/`In` on `json`, `jsonb`, `array`, `blob`... columns | **refused** by design (`OPAQUE_TYPES`, `dfb:Metadata/FieldCapabilities.php:27,46,53`) | |
| `Equals`/`In` (hence `choice`, `exact`) on an attribute that **ends on an association** (`workspace`, `language`, `sdsFile`) | **refused** at warm-up/compile of the criterion: "ends on an association: compare one of its fields (e.g. path.id)"; `IsNull` on a to-one association end is accepted, on a collection refused (`dfb:Metadata/FieldCapabilities.php:40-42,89-110`). Attributes must end on the identifier (`....workspace.id`). Affects most qk `choice`/`workspaces`/`exact` filters | |
| `relation` filter type, doctrine pager switches (`allowOutOfRangePages`, `fetchJoinCollection`) | no filter types at all in `dfb:src` (no `Filter/` dir). Pagination is plain LIMIT/OFFSET (`DoctrineQueryHandler.php:81-90`); pager out-of-range tolerance is the Pagerfanta bridge's | |

Consequence: the keres migration is **fully doable today**. The qk migration is **blocked on three Doctrine-bundle features** (named joins, JSON paths, expression sort) that the design (section 8.3) specifies but the code does not yet contain; everything qk-side that does not depend on them is listed so it can be done now.

---

## 1. keres-platform

### 1.1 Inventory (every use of the bundles)

| What | Where |
|---|---|
| composer: `sidus/admin-bundle ^5.0.6`, `sidus/datagrid-bundle ^4.0.2`, `sidus/filter-bundle ^6.0.3` (locked: admin 5.0.6, datagrid 4.0.2, filter 6.0.3) | `composer.json:21-24`, `composer.lock:2668,2724,2780` |
| bundles registered | `config/bundles.php:14-16` (`SidusFilterBundle`, `SidusDataGridBundle`, `SidusAdminBundle`) |
| No `config/packages/sidus_filter.yaml` (v6 default config only) | `grep sidus_filter config/packages` : nothing; `config/reference.php:1697` is the generated type map |
| List configs (4 lists, all `provider: doctrine`) | `config/datagrid/Game.yaml:33-89`, `User.yaml:22-`, `Feedback.yaml:19-`, `MailerError.yaml:12-` |
| Custom filter type `GamePlayerFilterType` + its service | `src/Service/Admin/GamePlayerFilterType.php:22-47`, `config/services.yaml:97-103` |
| Worker-mode registry decorators (exist only to work around v6 cached handlers) | `src/Service/Admin/ResettableQueryHandlerRegistry.php`, `ResettableDataGridRegistry.php`, `config/services.yaml:75-95` |
| Actions that reach into the v6 handler (`getAlias()`, `getQueryBuilder()`, `select NEW ...`) | `src/Action/Admin/GameListAction.php:39-61`, `UserListAction.php:39-59` |
| Form theme override extending `@SidusFilter/Form/fields.html.twig` | `templates/bundles/SidusDataGridBundle/Form/bootstrap4.html.twig:7-84`, registered in `config/packages/twig.yaml:5` |
| DataGrid Twig reading the v6 handler | `templates/bundles/SidusDataGridBundle/DataGrid/bootstrap4.html.twig:7` (`datagrid.queryHandler.pager`), `:74` (`datagrid.formView.sortable`), pager templates `Pager/pager.html.twig` (use `pager.hasPreviousPage()`, `pager.nbPages`, `pager.currentPage`, `page_path(...)`) |
| Tests touching filters or the admin lists | **none**: `tests/Functional/Action/*`, `tests/Unit/*` do not reference `GamePlayerFilterType`, `datagrid`, `sidus_data_grid` (grep over `tests/`: only `GuestBoardAdminToolsTest`, `AdminSeekAlertTest`, `GameVoterTest` mention "admin", none of them lists). `docs/multiplayer/*.md` mention sidus only as the HTML-action convention (`00-overview.md:271`) |
| Database | PostgreSQL 16 (`compose.yaml:59,189`), so `Dialect` is supported |

The `keres-multiplayer-testing` skill is about multiplayer feature testing; it has no filter coverage and was not needed for this analysis.

### 1.2 `GamePlayerFilterType`: what it does (exact semantics)

`src/Service/Admin/GamePlayerFilterType.php:24-46`:

- input: ignored unless a string with non-blank content after `trim()` (`:30`), no operator, a single text box (`config/services.yaml:102-103` gives it `game_player` + `TextType`);
- needle: `'%'.addcslashes(mb_strtolower(trim($data)), '%_\\').'%'` (`:36`): trimmed, Unicode-lowercased, `%`, `_`, `\` escaped, wrapped in `%...%` (substring);
- predicate (`:38-45`), one parameter reused three times: `EXISTS (SELECT 1 FROM GamePlayer gpf JOIN gpf.user uf WHERE gpf.game = <root> AND (LOWER(uf.username) LIKE :p OR LOWER(uf.email) LIKE :p OR LOWER(uf.displayName) LIKE :p))`;
- so: **OR** across the three attributes, **case-insensitive** (`LOWER` on both sides), **substring**, accents not folded, **NULL never matches** (`displayName` is nullable, `src/Entity/User.php:44`; `LOWER(NULL) LIKE ...` is NULL), "any player of the game" (either colour), one EXISTS so no row multiplication;
- why EXISTS: docblock `:18-20` (the v6 text filter would LEFT JOIN `players` once per attribute).

Configured at `config/datagrid/Game.yaml:41-43`:

```yaml
                filters:
                    player:
                        type: game_player
                        label: 'Player (username, email or name)'
```

Mapping facts: `Game::$players` is `OneToMany(GamePlayer, mappedBy: 'game')` (`src/Entity/Game.php:135-140`); `GamePlayer.user` is the to-one used by the join (`gpf.user`, `:39`).

### 1.3 v7 replacement: a stock `text` filter, the custom type is deleted

Every property of the v6 type is a core semantic of v7 + the Doctrine bundle:

| v6 behaviour | v7 | Evidence |
|---|---|---|
| OR over username/email/displayName | several `attributes` on one filter are combined with `AnyOf` (`OR`) | `docs/configuration.md` "Attributes"; `Filter/Type/AbstractFilterType.php:30-35` (`forAttributes` -> `AnyOf`); `dfb:Compiler/AnyOfCompiler.php:23` joins with `' OR '` |
| case-insensitive substring | `text` default operator `contains`, `case_sensitive` default `false` | `docs/filter-types.md` table; `Filter/Type/TextFilterType.php` (`'contains'` default operator, `case_sensitive` false); compiled `LOWER(col) LIKE :pN ESCAPE '!'` with `mb_strtolower` needle (`dfb:Compiler/AbstractTextCompiler.php:44-48`) |
| `%`, `_`, `\` literal | `%`, `_` literal (escape char is `!`, escaped too) | `AbstractTextCompiler.php:52-54` |
| null never matches | "Null never matches a comparison" | `docs/filter-types.md` "Criterion semantics"; `DqlExpression::nullable` |
| any player (either colour), no multiplication | a path through the to-many `players` compiles to one **correlated EXISTS per criterion** (`FROM App\Entity\GamePlayer x1_players WHERE x1_players.game = root AND LOWER(x1_players_user.username) LIKE ...`), pagination stays plain LIMIT/OFFSET | `dfb:Query/FieldAccess.php:85-115` (`subquery`: `mappedBy` branch, to-one step after the to-many is a `LEFT JOIN`) |

**YAML (target)**, in the `query_handler` block of `config/datagrid/Game.yaml` (v7 list schema; see section 3.1 for the rest of the list):

```yaml
                filters:
                    player:
                        type: text
                        attributes: [players.user.username, players.user.email, players.user.displayName]
                        form: { label: 'Player (username, email or name)' }
```

`type: game_player` and the `label:` key disappear (`label` moves under `form`, `docs/configuration.md` "form"). Public input name stays `player` (key default).

**Behavioural differences (flag to the owner):**

1. **Trimming**: v6 trims (`:30,36`); v7 `text` does not (`isBlank` is `null|''|[]` only, `Filter/Type/AbstractFilterType.php:20-23`; `TextFilterType::normalizeValue` casts without trimming). `" alice "` and `"  "` now filter on the spaces literally. Mitigation if wanted: trim in the controller before `ListInput`, or keep a 15-line custom type (skeleton below). Low risk for an admin search box, but it is a change.
2. **SQL shape**: three independent EXISTS joined by `OR` (one per attribute) instead of one EXISTS with three `OR`ed conditions. Same result set (EXISTS(a OR b) == EXISTS(a) OR EXISTS(b)); three small index-friendly subqueries on `idx_game_player_user_game`/`game_player.game_id` instead of one (`migrations/Version20260728092000.php:37-38`). [INFERENCE] negligible for an admin list; check with `EXPLAIN` if the table is large.
3. **Inner vs left join to `user`** inside the subquery: v6 `JOIN gpf.user`, v7 `LEFT JOIN`; a `GamePlayer` without user cannot match a `LIKE`, so identical.
4. Escape syntax differs (backslash vs `!`); no observable difference.
5. Parameters become `:p1,:p2,:p3` (one per criterion, same value bound three times) instead of one reused parameter.

**If the owner insists on trim semantics** (or wants a single EXISTS), keep a custom type; it emits core criteria only, no DQL:

```php
// src/Service/Admin/GamePlayerFilterType.php (v7)
final class GamePlayerFilterType extends AbstractFilterType  // Sidus\FilterBundle\Filter\Type\AbstractFilterType
{
    public static function getName(): string { return 'game_player'; }
    public static function configureOptions(OptionsResolver $resolver): void {}
    public static function getRequiredOperations(FilterDefinition $filter): array
    {
        return self::withAnyOf($filter, [Contains::class]);   // AnyOf is added when attributes > 1
    }
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (!\is_string($raw) || '' === ($text = trim($raw))) {
            return NormalizedValue::empty();
        }
        return NormalizedValue::of($text);
    }
    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $f) => new Contains($f, $value));
    }
}
```

It is autoconfigured (`FilterTypeInterface` -> tag `sidus_filter.filter_type`, `docs/DESIGN.md` section 18 "DI"), so the `config/services.yaml:97-103` block (with the `game_player`/`TextType` constructor args, a v6 mechanism) is deleted either way; the YAML is then `type: game_player` + the same `attributes:` + `form: {label}`. Recommended: use the stock `text` and accept difference 1.

### 1.4 The other three lists (rename only)

`Game.yaml` (`config/datagrid/Game.yaml:33-89`), BEFORE -> AFTER:

```yaml
# BEFORE (:34-39)
                provider: doctrine
                options: { entity: App\Entity\Game }
                sortable: [id, opponentTypeValue, speedCategoryValue, rated, endReasonValue, createdAt, gameOverAt]
                default_sort:
                    createdAt: DESC
# AFTER
                provider: doctrine
                options: { entity: App\Entity\Game }
                # pagination: omitted, see risk R1 (v6 default results_per_page is not set in these files)
                filters:
                    sort:
                        type: sort
                        options:
                            columns:
                                id: id
                                opponentTypeValue: opponentTypeValue
                                speedCategoryValue: speedCategoryValue
                                rated: rated
                                endReasonValue: endReasonValue
                                createdAt: createdAt
                                gameOverAt: gameOverAt
                            default: [{ column: createdAt, direction: desc }]
```

(`docs/filter-types.md` `sort`; `default` entries are `{column, direction}`, direction `asc|desc`, `Filter/Type/SortFilterType.php:62-71`.)

The `choice` filters (`opponentTypeValue`, `aiLevel`, `speedCategoryValue`, `rated`, `endReasonValue`, `Game.yaml:44-82`; `Feedback.yaml:28-42`) move `form_options.choices` to the filter's `options.choices` (the choices are now the allow-list, `docs/filter-types.md`: "with `choices`, an unknown value is a violation, and the configured (typed) value is emitted") and `label` to `form.label`:

```yaml
                    opponentTypeValue:
                        type: choice
                        options:
                            choices: { 'Versus engine': 0, 'Hot-seat': 1, 'Multiplayer': 2 }   # map label => value, docs/filter-types.md
                        form: { label: 'Mode' }
```

`createdAt`/`gameOverAt: type: date_range` and `deletedAt: type: not_null` (`Game.yaml:`, entries `createdAt`, `gameOverAt`, `deletedAt`, the file ends at `:89`) keep their names; `label: 'Ended'` / `'Deleted by a player only'` -> `form: {label: ...}`. `User.yaml:29-` `email: type: text`, `createdAt: date_range`, `sortable: [email, createdAt]`; `Feedback.yaml:23-26` `sortable: [id, category, createdAt, reviewed]`, `default_sort: {reviewed: ASC, createdAt: DESC}` (two keys -> `default: [{column: reviewed, direction: asc}, {column: createdAt, direction: desc}]`, `max_columns` default 3 OK); `MailerError.yaml:16-27` `sortable: [createdAt, errorClass]` + three `text` filters.

Semantic checks to do while migrating (flagged, not verified): `rated` is a PHP `bool` field (`src/Entity/Game.php:61`) with choices `{Rated: 1, Casual: 0}`: v7 emits `Equals(rated, 1)` with the configured int; whether DBAL binds it as boolean on PostgreSQL is [INFERENCE], prefer `{Rated: true, Casual: false}` (qk does exactly this for its boolean `image` filter, `SdsFileBundle/Resources/sidus_data_grid/SdsFile.yaml:56-60`). `date_range` bounds are inclusive and a date-only bound is interpreted in `timezone` (default UTC), `docs/filter-types.md`: check against the v6 behaviour for `createdAt`/`gameOverAt` (stored as `DateTimeImmutable`, `Game.php:80,86`).

### 1.5 `GameListAction` and `UserListAction` (the `NEW` projections)

BEFORE (`src/Action/Admin/GameListAction.php:44-61`, same pattern `UserListAction.php:43-59`):

```php
$alias = $queryHandler->getAlias();
$qb = $queryHandler->getQueryBuilder();
$qb->select(sprintf('NEW %s(%s, ...)', GameListRow::class, $alias, "(SELECT COUNT(gm.id) FROM App\\Entity\\GameMove gm WHERE gm.game = {$alias})", ...));
$dataGrid->handleRequest($request);
```

`getQueryBuilder()`/`getAlias()` are gone; the supported extension point is the per-request customizer (`DESIGN.md` 8.4; `dfb:Query/DoctrineQueryHandler.php:38-58`), which runs on the fresh builder **before** the criteria are applied:

```php
$handler = $registry->createQueryHandler('__sidus_datagrid.Game')   // or however the DataGrid section exposes the per-request handler
    ->withCustomizer(static function (DoctrineQueryContext $q): void {
        $root = $q->rootAlias();                                     // constant 'root' (DoctrineQueryHandlerFactory::ROOT_ALIAS)
        $q->queryBuilder()->select(sprintf('NEW %s(%s, %s, ...)', GameListRow::class, $root,
            "(SELECT COUNT(gm.id) FROM App\\Entity\\GameMove gm WHERE gm.game = {$root})", /* ...unchanged subselects... */));
    });
$result = $handler->handle($input);
```

Points that matter:

- The DQL subselects use `{$alias}`; keep them, replace `getAlias()` by `$q->rootAlias()` (`dfb:Query/DoctrineQueryContext.php` `rootAlias()`). Their own aliases (`gm`, `gpw`, `gpwu`, `uw`, `gpb`, `gpbu`, `ub`; `gp..gp4`, `gg`, `gpp` in the user action) must not collide with the bundle's aliases (`root`, `<path>_`, `x<N>_...`, `dfb:Query/JoinRegistry.php:12-22`, `FieldAccess.php:92-96`); they do not.
- **The count query stays valid**: `countNative` clones the builder, resets `select` and orderBy and selects `COUNT(root.id)` (`dfb:Query/DoctrineQueryHandler.php:100-111`), so the `NEW` select does not break the total (the v6 equivalent with `DoctrineORMAdapter` was a risk).
- The result items are the `GameListRow`/`UserListRow` objects (`getResult()` with `NEW`, `DoctrineQueryHandler.php:88-90`); the DataGrid columns keep resolving `game.id`, `user.email`... unchanged (`config/datagrid/Game.yaml:5-32`, `src/Model/Admin/UserListRow.php`).
- `UserListRow` has a correct note about raw driver strings for scalar subqueries in `NEW` (`src/Model/Admin/UserListRow.php`, constructor): unaffected by v7.
- No `requireDistinct()` needed: the subselects are scalar and correlated; no collection is joined.
- Where the customizer is registered depends on how the DataGrid bundle 5.0 exposes the handler (DataGrid section). `instanceof DoctrineQueryHandler` checks (`GameListAction.php:41-43`, `UserListAction.php:40-42`) must be retargeted to `Sidus\DoctrineFilterBundle\Query\DoctrineQueryHandler`, whose `withCustomizer()` returns a **clone** (`DoctrineQueryHandler.php:50-55`): use the returned instance.

### 1.6 Worker-mode decorators: delete

`ResettableDataGridRegistry` and `ResettableQueryHandlerRegistry` exist because v6 cached handlers (their docblocks, `ResettableDataGridRegistry.php` class comment, `ResettableQueryHandlerRegistry.php` class comment) and read private properties `queryHandlers`, `queryHandlerConfigurations`, `rawQueryHandlerConfigurations` of the v6 registry by reflection (`ResettableQueryHandlerRegistry.php`, constructor). In v7 the registry holds only immutable definitions and returns a new handler per call (`UPGRADE-7.0.md` "Why (worker mode)"); these properties no longer exist, so the classes **would crash at construction** if kept (`ReflectionClass::getProperty` throws). Delete both classes and `config/services.yaml:75-95`. The decorator targets `Sidus\FilterBundle\Registry\QueryHandlerRegistry` as a v6 same-FQCN class (`services.yaml:92`), another reason not to keep them. `DataGridRegistry` (`ResettableDataGridRegistry`) is DataGrid-5.0 territory: per `UPGRADE-7.0.md` "SidusDataGridBundle" it keeps configurations only, so the same deletion applies [INFERENCE: depends on datagrid-bundle 5.0 being what the DataGrid section says].

### 1.7 Templates

- `templates/bundles/SidusDataGridBundle/Form/bootstrap4.html.twig:7` `{% use '@SidusFilter/Form/fields.html.twig' %}` -> **path no longer exists** (`Resources/views/Form/*.twig` became `templates/form/*.html.twig`, i.e. `@SidusFilter/form/base.html.twig` / `bootstrap_6.html.twig` / `bulma_1.html.twig`, `UPGRADE-7.0.md` table). This design-system theme re-implements blocks by v6 names; they must be rewritten against `_blocks.html.twig` (the shipped themes `use` it, `DESIGN.md` 18 "Themes"). Blocks to port:
  - `sidus_combo_filter_widget` (`:9-14`, `form.input`/`form.option`) -> the operator/value block of `OperatorValueType` (v6 `ComboFilterType` renamed);
  - `sidus_order_button_widget` (`:16-23`, one button per sortable column with `name="{{ form.vars.full_name }}"`) -> sort field `SortType`, one button per `vars.columns[key]` posting `next` (`DESIGN.md` 18);
  - `sidus_date_range_widget` (`:25-41`, `form.startDate`/`form.endDate`, `sidus.filter.date_range.to`) -> children are now **`from` and `to`** (`UPGRADE-7.0.md` `DateRangeType`); translation key `sidus.filter.date_range.to` is unchanged.
  - `sidus_link_widget`/`sidus_link_row` (`:43-84`) belong to the DataGrid/Admin bundles (`AdminLinkType`), not to the filter bundle: leave.
  `config/packages/twig.yaml:5` registers the file by its `@SidusDataGrid/Form/bootstrap4.html.twig` logical name: whether that name survives depends on datagrid-bundle 5.0 (DataGrid section).
- `templates/bundles/SidusDataGridBundle/DataGrid/bootstrap4.html.twig:7` `datagrid.queryHandler.pager` -> `datagrid.pager` (built with `ListResultAdapter::createPager`); `:74` `datagrid.formView.sortable` -> `datagrid.formView.sort[key]` (`DESIGN.md` 14) or `formView.filters.<sortFilterName>.vars.columns[key]` (`UPGRADE-7.0.md`); `pager.count`, `currentPageResults`, `currentPageOffsetStart/End` (`:40-43`) are Pagerfanta APIs, unchanged. The 3 sort-related blocks of `table_headers.html.twig` live in the DataGrid bundle, not in keres.
- Translations: any `sidus.filter.combo.*` key in keres translations -> `sidus.filter.operator.*` (not found in the grep of `templates/`; translations not searched [INFERENCE: check `translations/`]).

### 1.8 keres checklist (ordered)

1. composer: `sidus/filter-bundle ^7.0`, add `sidus/doctrine-filter-bundle`, `sidus/datagrid-bundle` 5.x (`composer.json:21-24`); register `SidusDoctrineFilterBundle` in `config/bundles.php`.
2. Delete `GamePlayerFilterType.php` and `config/services.yaml:97-103`; rewrite `Game.yaml` per 1.3/1.4; rename keys in the 3 other lists.
3. Delete `ResettableQueryHandlerRegistry.php`, `ResettableDataGridRegistry.php`, `config/services.yaml:75-95`.
4. Port `GameListAction`/`UserListAction` to customizers (1.5), after the DataGrid section's handler access is settled.
5. Rewrite `templates/bundles/SidusDataGridBundle/Form/bootstrap4.html.twig` (and DataGrid/pager templates) per 1.7.
6. `cache:clear`/`cache:warmup` (invalid choice/sort config fails here), then `bin/console debug:sidus-filter Game` and the Doctrine bundle's lint command if present (`dfb:config/services.php` registers `LintCommand`, name not read [INFERENCE]).
7. Regenerate `config/reference.php` (generated type map, `:1446`, `:1697`).

**Deletable**: `src/Service/Admin/GamePlayerFilterType.php`, `ResettableDataGridRegistry.php`, `ResettableQueryHandlerRegistry.php`. **Stays**: `GameListRow`, `UserListRow`, row templates `templates/admin/datagrid/*.twig`, all entities, the `NEW` select SQL.

**Risks / open questions (keres):**
- R1: `results_per_page` is not set in any keres list, so the v6 default applied; v7 `pagination.per_page` default is not documented in the files read: set it explicitly if the v6 default (not read; `vendor/` absent for keres) must be kept.
- R2: trim semantics (1.3, difference 1).
- R3: how the DataGrid 5.0 exposes the handler to actions (needed for the customizers): not part of this section.
- R4: `rated` bool choice binding (1.4).

---

## 2. qk-sds-api

### 2.0 Baseline facts

- qk is on **filter-bundle 5.0.2, datagrid 4.0.0**, not 6.x: `composer.json:40-41` (`^4.0`, `^5.0`), `composer.lock:6075,6131`, `vendor/sidus/filter-bundle` tag `v5.0.2` with the v5 layout (no `src/`). The v6 migration notes were mapped from FQCNs actually used by qk (`Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandler`, `DTO\SortConfig`, `Pagination\DoctrineORMAdapter`, `Factory\QueryHandlerFactoryInterface`), all listed as moved/removed in `UPGRADE-7.0.md`; qk goes 5.0.2 -> 7.0 directly, behaviour differences between 5 and 6 were not diffed [INFERENCE].
- List YAML is loaded from every bundle's `Resources/sidus_data_grid/*.yaml` (`AnalysisBundle/Bundle/AbstractBundle.php:75-77`); 33 files, 2337 lines. Filter type usage count over all of them (`grep type:`): `advanced_text` 77, `custom_choice` 42, `advanced_number` 41, `choice` 31, `date_range` 24, `is_null` 8, `workspaces` 6, `hash` 6, `regexp` 5, `entity` 3, `exact`/`not_null`/`unit_category`/`validation_category`/`sds_file_text` 1 each (the other `type:` hits are chart/column types).
- Database: PostgreSQL (`DoctrineQueryHandler` fork emits `JSON_GET_FIELD`, `config/packages/doctrine.yaml:94-145` registers martin-georgiev functions): supported dialect.

### 2.1 Inventory of filter-related code

| Item | Path |
|---|---|
| Fork of the Doctrine handler (named joins, JSON paths, computed sort, pager switches) | `SdsAnalysis/AdminBundle/Query/Handler/DoctrineQueryHandler.php:12-210` |
| Fork's factory, re-registers provider `doctrine` | `.../Query/Factory/DoctrineQueryHandlerFactory.php:14-38`, tag `sidus.query_handler_factory` at `AdminBundle/Resources/config/services.yaml:15-17` |
| Custom types: `HashFilter`, `NotEmptyFilterType`, `NullFilterType`, `RegexpFilterType`, `SdsFileTextFilter`, `WorkspaceFilterType` | `AdminBundle/Filter/Type/*.php`; registered `AdminBundle/Resources/config/filters.yaml:7-97` (regexp `:7`, not_empty `:21`, is_null `:28`, workspaces `:35`, sds_file_text `:44`, hash `:54`) |
| Other custom types | `Section9Bundle/Filter/UnitCategoryFilterType.php:9-22` (+ `Section9Bundle/Resources/config/filters.yaml:7-12`), `ValidationBundle/Filter/ValidationCategoryFilterType.php:12-64` (+ `ValidationBundle/Resources/config/services.yaml:19`) |
| In-memory provider | `LogAnalyserBundle/Query/Handler/InMemoryQueryHandler.php`, `Query/Factory/InMemoryQueryHandlerFactory.php`, `Query/Filter/Type/{AbstractInMemory,AdvancedText,Choice,DateRange}FilterType.php`, `Resources/config/services.yaml` |
| Actions touching handlers | `PageBundle/Action/Line/ListAction.php:30-64`, `PageBundle/Action/SdsFile/ListAction.php:53-156`, `ValidationBundle/Action/SdsFileValidationStatistics/ListAction.php:38-114`, `LogAnalyserBundle/Action/ListAction.php:31-38`, `ReadAction.php:30-47` |
| Twig on v6 handler | `AdminBundle/Resources/views/Macros/utilities.html.twig:26-28` (`datagrid.queryHandler.configuration.sortable`, `datagrid.formView.sortable[index]`), `ValidationBundle/Resources/views/datagrid/validation.html.twig:80` (`datagrid.formView.sortable`) |
| App DQL functions (already registered, keep) | `config/packages/doctrine.yaml:44-48` (`CAST`, `ENCODE`, `INVERT_REGEXP`), `:94-145` (`JSON_GET_FIELD*`, `JSONB_EXISTS`, `ILIKE`, `REGEXP`, `IREGEXP`, `NOT_REGEXP`, `NOT_IREGEXP`) |
| Tests | none for filters (`tests/` holds `Analysis`, `Sds`, `ValidationItem` fixtures, downloaders) |

### 2.2 The `DoctrineQueryHandler` fork: delete, feature by feature

The fork re-registers the provider name `doctrine` (`DoctrineQueryHandlerFactory.php:34-37`). In v7 "a provider registered twice is an error" (`DESIGN.md` 18, `ListDefinitionPass`): the fork factory, handler and tag **must be deleted**, not kept alongside the Doctrine bundle. Mapping of each fork responsibility:

#### 2.2.1 Named joins (`options.joins`), fork lines 49-95

v6 syntax (`PageBundle/Resources/sidus_data_grid/Line.yaml:10-13`):

```yaml
options:
    entity: Quarks\SdsAnalysis\PageBundle\Entity\Line
    joins:
        Quarks\SdsAnalysis\LanguageFinderBundle\Entity\SdsFileLanguage:   # key = entity class
            alias: SdsFileLanguage
            condition: [ sdsFile, page.pages.sdsFile ]                    # alias.sdsFile = <resolved path>
```

Fork semantics (`DoctrineQueryHandler.php:49-95`): key = class, `alias`, then either `reference: <field>` => `alias.field = root` (`:73-80`) or `condition: [field, path]` => `alias.field = <path resolved, recursively through other joins>` (`:59-72`); `leftJoin(class, alias, WITH, ...)` once per handler on first attribute starting `<alias>.` (`:53-56,83-89`). **Always LEFT, always shared**, so a to-many join (Pages/Page/Line from SdsFile) multiplies rows and every filter on it hits the **same** element.

v7 target (`DESIGN.md` 8.3):

```yaml
options:
    entity: Quarks\SdsAnalysis\PageBundle\Entity\Line
    joins:
        SdsFileLanguage:                                   # alias, usable as a path root
            entity: Quarks\SdsAnalysis\LanguageFinderBundle\Entity\SdsFileLanguage
            on: { field: sdsFile, equals: page.pages.sdsFile }
            cardinality: one
```

and the filters keep `attributes: [ SdsFileLanguage.language ]` for `is_null` (`Line.yaml:29`) but need `SdsFileLanguage.language.id` for the `choice` (`Line.yaml:32`), see section 0 (association ends). Per-file translation of the six v6 joins:

| v6 | v7 |
|---|---|
| `Line.yaml:11-13` `SdsFileLanguage`, `condition: [sdsFile, page.pages.sdsFile]` | `on: {field: sdsFile, equals: page.pages.sdsFile}`, `cardinality: one` (the design example is literally this join, `DESIGN.md` 8.3) |
| `SdsFile.yaml:11-13`, `SdsFileContent.yaml:10-12` `SdsFileRevisionDate`, `reference: sdsFile` | `on: {field: sdsFile, equals: <the root entity>}`: **syntax for "the root" is not specified** (design only shows a path; `reference` has no v7 spelling) [open question O1]; `cardinality: one` (used as `date_range`/`is_null` on `SdsFileRevisionDate.revisionDate`, `SdsFile.yaml:37-42`) |
| `SdsFileContent.yaml:13-21` `Pages` (`reference: sdsFile`), `Page` (`condition: [pages, Pages.id]`), `Line` (`condition: [page, Page.id]`) | chain Pages -> Page -> Line, **`cardinality: many`**, **`shared: true`** (see below) |

`cardinality: many` compiles each criterion as an EXISTS ("`cardinality: many` names are compiled like to-many paths (EXISTS) unless `shared: true`", `DESIGN.md` 8.3). **This is a deliberate semantic decision per list:**

- `SdsFileContent` (`SdsFileContent.yaml:77-128`: `sectionNumber`, `subsectionNumber`, `header`, `text`, `regexp`, `pageNumber`, `lineNumber` all on `Line.*`/`Page.*`) is paired with the `SdsFileInnerLines` grid that shows **the lines matching all those filters together** (`SdsFile/ListAction.php:72-77`). To keep "the same line satisfies all filters" in the SdsFile result list, use `shared: true` on `Pages`/`Page`/`Line`; the list then runs in DISTINCT mode (`requireDistinct`, `dfb:Query/DoctrineQueryContext.php`; "reported by `debug:sidus-filter`", `DESIGN.md` 8.2). Without `shared`, "text contains X" and "header = yes" become two independent "any line" tests: an SdsFile matches when X is on one line and a header on another. [INFERENCE on intent: derived from the v6 shared-join behaviour and the inner-lines grid]. Owner must confirm.
- `Line.yaml` (root = `Line`) only joins `SdsFileLanguage` (one-to-one-ish): `cardinality: one`.

**Not buildable today**: `joins` is not read anywhere in `dfb:src` (section 0). The chain's `equals: Pages.id` (join referencing another named join's alias, v6 `:67-71`) is a case the design text does not describe either [open question O2]. Until implemented, the only working escape is `native` (section 2.3.5) or a customizer.

#### 2.2.2 JSON paths and casts, fork lines 20-47, 105-126, 200-209

v6 syntax inside attribute strings, e.g. `SdsFileValidationStatistics.yaml:23,26`:

```yaml
totalRegressions:
    attributes: [ totals->regression::int ]
    type: advanced_number
```

fork compiles at request time to `CAST(JSON_GET_FIELD(e.totals, 'regression') AS int)` (`DoctrineQueryHandler.php:113-123`; the cast string goes into DQL unvalidated).

v7 (`docs/configuration.md` "Attributes", `DESIGN.md` 8.1/8.3, 18 "Fields"): the string form is **still accepted** (parsed at compile time, cast aliases `int`/`bool`), the canonical form is:

```yaml
totalRegressions:
    type: number
    attributes: [ { path: totals, json: [regression], cast: integer } ]
    options: { operators: [eq, neq, gt, gte, lt, lte] }     # was advanced_number
```

Dynamic filters generated in the ListAction (`ValidationBundle/Action/SdsFileValidationStatistics/ListAction.php:98-113`): `"statistics->{$category}->regression::int"` => `{ path: statistics, json: [<category>, regression], cast: integer }`.

Requirements:

- A JSON compiler: "Compilation goes through a `JsonPathCompilerInterface` service; the shipped one targets the martin-georgiev functions (`JSON_GET_FIELD`, `JSON_GET_FIELD_AS_TEXT`, `JSON_GET_FIELD_AS_INTEGER`); a JSON field without a registered compiler is a warm-up error" (`DESIGN.md` 8.3). qk already registers exactly these functions (`config/packages/doctrine.yaml:94-96`), so no app config is needed once the bundle ships it. **Not implemented in `dfb:src`** (`FieldCapabilities.php:36-37,63-64` refuses; no such interface): blocker B1.
- Entity columns: `totals`/`statistics` must resolve to a JSON-typed mapped field (`SdsFileValidationStatistics::$statistics` is `json_document` jsonb, `ValidationBundle/Entity/SdsFileValidationStatistics.php:25-26`; `totals` is in `ValidationStatisticsTrait`, not read).
- The v6 filter `advanced_number` on a casted JSON value becomes `number`; `Range`/`Equals` on a JSON-derived field depend on the JSON compiler's types (cast `integer`).

#### 2.2.3 Computed sort columns `statistics_<property>`, fork lines 128-198

v6: `applySort` first tries `resolveAttributeAlias($column)`; on `UnexpectedValueException`, if the entity is `SdsFileValidationStatistics` and the column starts with `statistics_` (`:141-146`), it adds three hidden selects (`valid/NULLIF(total,0)` as `statistics_<p>`, `CASE WHEN total = 0 THEN 0 ELSE 1 END` as `..._empty_last`, `valid` as `..._higher_better`, `:152-194`) and orders by `_empty_last`, the ratio, `_higher_better` all in the requested direction (`:195-197`). The sort columns are added at runtime by the action (`ListAction.php:82-91`: `$sortColumn = "statistics_{$category}"`, `getConfiguration()->addSortable($sortColumn)`).

v7 (`DESIGN.md` 8.3, "Expression sort columns"): a `sort` column with `provider_options.doctrine.expression: '...{root}...'` (allow-listed because it is configuration). Target shape for one category (the config key `statistics_<category>` is public, the DQL is static config):

```yaml
filters:
    sort:
        type: sort
        options:
            columns:
                sdsFile.filename: sdsFile.filename
                total: total
                statistics_revision_date:
                    field: statistics            # the JSON column, only used to type the column [INFERENCE]
                    provider_options:
                        doctrine:
                            expression: >-
                                JSON_GET_FIELD_AS_INTEGER(JSON_GET_FIELD({root}.statistics, 'revision_date'), 'valid')
                                / NULLIF(JSON_GET_FIELD_AS_INTEGER(JSON_GET_FIELD({root}.statistics, 'revision_date'), 'total'), 0)
```

Gaps vs v6 (blocker B3 + open question O3):
1. **Not implemented** (`SortCompiler.php:35`: only `aggregate`).
2. v6 orders by **three** keys per column (`:195-197`), design 8.3 describes **one** expression per column. Either `expression` must accept an ordered list of keys (`{expr, direction: same|asc|desc}`), or the tie-breakers are lost (the "empty last" and "higher is better" rules). Recommended: extend `expression` to a list of order keys; one-line semantics change in `SortCompiler`.
3. The category list is `quarks.validation.config` (`config/admin/validation.yaml:2`), unknown at YAML-authoring time; the columns **must be generated at container compile time** (definitions are immutable, runtime `addSortable`/`addFilter`, `ListAction.php:94-113`, no longer exist). Generate with a PHP config file that reads `config/admin/validation.yaml` (example in 2.5).
4. Nulls: v7 sorts nulls first ascending / last descending (`SortCompiler.php:57-59`), v6 PostgreSQL default is NULLS LAST ascending: **direction flips for null ratios** (total = 0 gives `NULLIF` -> NULL). v6 compensated with `_empty_last`; keep an explicit key.

#### 2.2.4 Pager switches `allowOutOfRangePages`, `fetchJoinCollection` (fork `:16,18,97-103`)

- `fetchJoinCollection`: default `false`; the only writer in the repo is the fork (grep: no other assignment): drop. v7 uses ORM `Paginator` iff `requireDistinct()` (`dfb:Query/DoctrineQueryHandler.php:88-90`).
- `allowOutOfRangePages`: set to `true` only in `PageBundle/Action/Line/ListAction.php:38`, together with `$adapter->setNbResults(-1)` on `ManualCountAdapterInterface` (`:56-61`): the Line grid skips the COUNT. v7: `count: none` on the `Line` list (`docs/configuration.md` "Pagination and count": total `null`, `hasNextPage()` exact via `per_page + 1`) and `ListResultAdapter::createPager` already "allows out-of-range pages" (`DESIGN.md` 18 "Pagerfanta bridge"). Delete the whole `allowOutOfRangePages`/`setNbResults` block of the action.

```yaml
# Line list, AFTER
provider: doctrine
pagination: { per_page: 50 }          # results_per_page: 50 (Line.yaml:7)
count: none
```

#### 2.2.5 `resolveAttributeAlias` / `getAttributeMetadata` public use

Only the fork's own code and commented lines in `Line/ListAction.php:39-51` use them (grep): nothing to port.

### 2.3 Custom filter types

#### 2.3.1 `HashFilter` (`AdminBundle/Filter/Type/HashFilter.php:10-26`), 6 usages

Behaviour: input string; if length != 64: lowercase, strip `%`, `ILIKE(encode(col,'hex'), :p%) = TRUE` (prefix match on the hex form, `:16-20`); if exactly 64 chars: `col = hex2bin(data)` bound as `Types::BINARY` (`:22-24`). Column type: `binary` (`SdsFileBundle/Entity/SdsFile.php:49-50`). Usages: `SdsFile.yaml:32-33` (attribute default = `hash`), `SdsFileContent.yaml:28-29`, `AnalysisEvent.yaml:18-20` (`sdsFile.hash`), `SdsFileValidationStatistics.yaml:31-33` (`sdsFile.hash`), `AnalysisValidationItem.yaml:19-21` (`sdsFileAnalysisValidation.sdsFile.hash`), `PhysicalAndChemicalProperty.yaml:17-19` (`sdsFilePhysicalAndChemicalProperties.sdsFile.hash`, a **to-many** prefix by its plural name, [INFERENCE: entity not read]).

**Decision: custom type + new criterion + Doctrine compiler**, not `Native`.
- No core criterion expresses "hex prefix of a binary column" and `Equals` on a raw hex string would not bind as binary; so it needs provider code.
- `Native('doctrine', Closure)` runs a DQL closure on the *root*: `JoinRegistry::alias()` throws on to-many prefixes (`dfb:Query/JoinRegistry.php:62-66`), so the 2 to-many/long-path usages would need hand-written EXISTS DQL in a closure. A compiler built on `FieldAccess::matching()` gets to-one sharing and to-many EXISTS **for free** (`dfb:Query/FieldAccess.php:28-45`), which is the whole point of the new design, and `docs/providers.md` prescribes "new criterion class + a compiler class per provider".
- Cost: three small classes, one of which (the compiler) is Doctrine-specific.

Skeleton:

```php
// src/Quarks/SdsAnalysis/AdminBundle/Criterion/HashMatch.php   (provider-independent value object)
final readonly class HashMatch extends FieldCriterion
{
    /** @param string $value 64 hex chars (exact) or a hex prefix (1..63 chars), lower-case */
    public function __construct(Field|string $field, public string $value) { parent::__construct($field); }
}

// .../Filter/Type/HashFilterType.php   (autoconfigured, tag sidus_filter.filter_type)
final class HashFilterType extends AbstractFilterType
{
    public static function getName(): string { return 'hash'; }
    public static function configureOptions(OptionsResolver $resolver): void {}
    public static function getRequiredOperations(FilterDefinition $filter): array { return self::withAnyOf($filter, [HashMatch::class]); }
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) { return NormalizedValue::empty(); }
        if (!\is_string($raw)) { return NormalizedValue::invalid(new Violation('', 'Expected a hex string.', $raw)); }
        $hex = strtolower(str_replace('%', '', trim($raw)));          // v6: strtolower + strip '%' (HashFilter.php:17)
        if ('' === $hex || !ctype_xdigit($hex) || \strlen($hex) > 64) { return NormalizedValue::invalid(new Violation('', 'Expected 1 to 64 hexadecimal characters.', $raw)); }
        return NormalizedValue::of($hex);
    }
    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    { return $this->forAttributes($filter, static fn (Field $f) => new HashMatch($f, $value)); }
}

// .../Doctrine/HashMatchCompiler.php   (autoconfigured: DoctrineCriterionCompilerInterface -> tag sidus_doctrine_filter.compiler)
final class HashMatchCompiler extends AbstractDoctrineCompiler
{
    public static function getCriterionClass(): string { return HashMatch::class; }
    public function compile(Criterion $criterion, DoctrineCompiler $root, DoctrineQueryContext $context): DqlExpression
    {
        \assert($criterion instanceof HashMatch);
        $field = $context->mapping->fields->resolve($criterion->field);   // not $this->resolve(): FieldCapabilities has no rule for this class
        return FieldAccess::matching($context, $field, function (string $column) use ($criterion, $context): DqlExpression {
            if (64 === \strlen($criterion->value)) {
                return DqlExpression::nullable($column.' = '.$context->parameter(hex2bin($criterion->value), \Doctrine\DBAL\Types\Types::BINARY));
            }
            // ENCODE is the app's DQL function (config/packages/doctrine.yaml:48), SIDUS_ILIKE the bundle's (dfb:Dql/DqlFunctions.php:20)
            return DqlExpression::safe(DqlFunctions::ILIKE."(ENCODE({$column}, 'hex'), ".$context->parameter($criterion->value.'%').') = 1');
        });
    }
}
```

Config: unchanged key (`type: hash`, `attributes:`), remove the service in `filters.yaml:54-57` (`$name`, `$formType`). Differences: invalid input (non-hex, >64 chars) is now a violation instead of a silently non-matching prefix; v6 accepted any string for the prefix. [INFERENCE] that `SIDUS_ILIKE(ENCODE(...), :p)` parses (nested function as first arg of a custom numeric function): to be verified by the one test below. `ENCODE` is registered as a string function by the app (`doctrine.yaml:48`).

#### 2.3.2 `NullFilterType` (`is_null`, 8 usages) and `NotEmptyFilterType`

- `NullFilterType.php:14-22`: checkbox; `{col} IS NULL` when truthy, ignored otherwise => core `null` type (`docs/filter-types.md`: `null`/`not_null`, boolean flag => `IsNull`). Rename `type: is_null` -> `type: null` in `Line.yaml:30`, `SdsFile.yaml:42,55`, `SdsFileContent.yaml:38,51`, `SdsFileValidationStatistics.yaml:46,61`, `Section9Category.yaml:31`; delete `NullFilterType.php` and `filters.yaml:28-33`. Check each attribute type: `sds.language`, `SdsFileRevisionDate.revisionDate`, `language` are association/date fields: OK (`IsNull` on a to-one relation = no related object, `docs/filter-types.md`); **`Section9Category.yaml:29-31` `emptyUnits: attributes: [unitCategories], type: is_null` targets a `json` column (`Section9Bundle/Entity/Section9Category.php:37`): the Doctrine bundle refuses `IsNull` on `json` (`dfb:Metadata/FieldCapabilities.php:27,53`)**, see 2.3.4.
- `NotEmptyFilterType.php:15-25`: no WHERE at all; adds an INNER JOIN as side effect (`getAttributeMetadata($path, INNER_JOIN)`), ignores the data. It is **registered (`filters.yaml:21-27`) but referenced by no YAML** (grep `type: not_empty` over `qk:src`: only the registration). Delete type and registration. If ever needed: core `not_empty` (`Not(IsEmpty)`).

#### 2.3.3 `RegexpFilterType` (5 usages) -> core `regexp` + `Matches`

v6 (`RegexpFilterType.php:11-27`): combo `regexp|not_regexp|iregexp|not_iregexp` -> DQL functions `REGEXP`, `NOT_REGEXP`, `IREGEXP`, `NOT_IREGEXP` (martin-georgiev, registered `doctrine.yaml:138-141`). Usages: `Line.yaml:88-90`, `SdsFileContent.yaml:120-122`, `SdsFileInnerLines.yaml:55-57`, `PhysicalAndChemicalProperty.yaml:22-29` (`labelRegexp`, `rawValueRegexp`).

v7: `type: regexp`, operators `matches|not_matches|imatches|not_imatches` (`docs/filter-types.md`; default: all four). Conditions:

- The **bundle registers its own functions** `SIDUS_REGEXP`/`SIDUS_IREGEXP` (PostgreSQL `~`/`~*`) when a handler is created (`dfb:Dql/DqlFunctions.php:23-33`, `Compiler/MatchesCompiler.php:21-34`): the app's `REGEXP`/`IREGEXP`/`NOT_*` declarations are not used by v7 and may be removed from `doctrine.yaml:138-141` only if nothing else uses them (grep not done for other usages: `JSONB_EXISTS`, `ILIKE`, `ENCODE`, `CAST` are still needed by sections 2.3.1/2.3.4/2.2.3).
- **Pattern restriction (behaviour change)**: patterns must stay in a portable POSIX ERE subset: no `(?`, no `\d`/backreferences, no possessive quantifiers, max 255 bytes, unanchored (`docs/filter-types.md`). v6 passed any PostgreSQL regex, including `\d`, `(?i)`. Users of the regexp boxes will get violations for such patterns; note `[[:digit:]]` instead of `\d`.
- Not-matching on null: `Not(Matches)` is true for null (null never matches a comparison), v6 `NOT_REGEXP(col) = true` is NULL (excluded) for null columns: **result sets for `not_matches` grow by the null rows**.
- Capability: `Matches` has a compiler, so the strict compile-time check passes (a provider without it would fail container compilation, `docs/providers.md` "Strictness").

```yaml
# BEFORE  SdsFileContent.yaml:120-122
regexp:
    attributes: [ Line.text ]
    type: regexp
# AFTER
regexp:
    attributes: [ Line.text ]
    type: regexp
    form: { label: Regexp }
```

Delete `RegexpFilterType.php` and `filters.yaml:7-20`.

#### 2.3.4 `UnitCategoryFilterType` (1 usage) and `ValidationCategoryFilterType` (1 usage)

- `UnitCategoryFilterType.php:11-21`: `AND` of `JSONB_EXISTS(CAST(col AS jsonb), :item) = TRUE` for each selected item ("contains all these keys") on a `json` column (`Section9Category.yaml:23-28`). No core criterion for JSON containment; JSON columns are refused for core criteria. Port as **`native` + builder** (no new criterion needed for a single use):

```yaml
unitCategories:
    type: native
    providers: [doctrine]
    options: { builder: Quarks\SdsAnalysis\Section9Bundle\Filter\UnitCategoryNativeBuilder }
    form: { type: Quarks\SdsAnalysis\Section9Bundle\Form\Type\UnitCategoryType, options: { multiple: true } }
```

```php
final class UnitCategoryNativeBuilder implements NativeCriterionBuilderInterface   // autoconfigured, service id = FQCN
{
    public function __invoke(FilterDefinition $filter, mixed $value): ?Native
    {
        $items = array_values(array_filter((array) $value, 'is_string'));
        if ([] === $items) { return null; }
        return new Native('doctrine', static function (DoctrineQueryContext $q) use ($items): string {
            $column = $q->rootAlias().'.unitCategories';
            return implode(' AND ', array_map(static fn (string $i): string => "JSONB_EXISTS(CAST({$column} AS jsonb), ".$q->parameter($i).') = TRUE', $items));
        });
    }
}
```
  (`dfb:Compiler/NativeCompiler.php:21-32`: payload closure returns the DQL; it is wrapped in parentheses and ANDed.) Note the `native` input is not validated by the type (`NativeFilterType.php`: any non-blank value passes): the builder does the filtering above. A `native` filter has **no form field by default** (no form mapper, `DESIGN.md` 18 "Form adapter": "a filter type without a mapper (e.g. `native`) has no field"), so the `form: {type: ...}` must be honoured by the form adapter [open question O4: does `form.type` create a field for a mapper-less type? not verified in `src/Form`].
  `emptyUnits` (`is_null` on the same `json` column) is refused by the Doctrine bundle (`FieldCapabilities.php:53`): second `native` builder returning `Native('doctrine', fn ($q) => $q->rootAlias().'.unitCategories IS NULL')` (flag input), or restrict to the SQL JSON `null` semantics: owner decision.

- `ValidationCategoryFilterType.php:26-63`: takes a category key, looks it up in `$validationConfig` (the `quarks.validation.config` parameter, `config/admin/validation.yaml:2`), adds `e.class = :c` and `e.propertyPath LIKE :p1 OR ...` (`[]` -> `[%]`). It is a compile-time allow-list that needs injected configuration:

Port as a **custom type** (it needs the injected `$validationConfig`) whose `buildCriterion()` emits a `native` criterion; nothing in the core vocabulary expresses `LIKE 'a[%]b'` (`Contains`/`StartsWith`/`EndsWith` treat `%` as a literal, `docs/filter-types.md`), and the v6 pattern `[]` -> `[%]` (`ValidationCategoryFilterType.php:57`) is a mid-string wildcard:

```php
final class ValidationCategoryFilterType extends AbstractFilterType    // autoconfigured, bind $validationConfig: '%quarks.validation.config%' (ValidationBundle/Resources/config/services.yaml:8)
{
    public function __construct(private readonly array $validationConfig) {}
    public static function getName(): string { return 'validation_category'; }
    public static function configureOptions(OptionsResolver $resolver): void {}
    public static function getRequiredOperations(FilterDefinition $filter): array { return [Native::class]; }
    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        if (self::isBlank($raw)) { return NormalizedValue::empty(); }
        return \is_string($raw) && isset($this->validationConfig[$raw]['class'])
            ? NormalizedValue::of($raw)
            : NormalizedValue::invalid(new Violation('', 'Unknown category.', $raw));   // v6 silently ignored it (:37-40)
    }
    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        $config = $this->validationConfig[$value];
        return new Native('doctrine', static function (DoctrineQueryContext $q) use ($config): string {
            $root = $q->rootAlias();
            $dql = [$root.'.class = '.$q->parameter($config['class'])];                       // v6 :49-50
            $like = array_map(static fn (string $p): string => $root.'.propertyPath LIKE '.$q->parameter(str_replace('[]', '[%]', $p)), $config['properties'] ?? []);   // v6 :54-58
            if ([] !== $like) { $dql[] = '('.implode(' OR ', $like).')'; }
            return implode(' AND ', $dql);
        });
    }
}
```

Restrict the filter with `providers: [doctrine]` in the YAML (`AnalysisValidationItem.yaml:14-15`). The type is a regular service, so the `native` filter type/builder indirection is not needed here.

#### 2.3.5 `SdsFileTextFilter` (`sds_file_text`, 1 usage `SdsFile.yaml:30-31`)

`SdsFileTextFilter.php:18-37`: when the advanced-text combo is non-empty, **inner-joins** `Pages`, `Page`, `Line` as unrelated entities (`Pages.sdsFile = root`, `Page.pages = Pages.id`, `Line.page = Page.id`, `:30-32`; fixed aliases `pages`, `page`, `line`, which collide with the fork's named joins in the SdsFileContent list) and applies the advanced-text DQL on `line.text` (`:34-36`): full-text search across all lines of the file. It multiplies rows (one per matching line) and relies on pager de-duplication that does not exist (`fetchJoinCollection=false`, fork `:18`): [INFERENCE] the SdsFile grid shows duplicates when several lines match.

v7 options:
- **A (target, blocked by B2)**: `type: text` with `attributes: [Line.text]` plus named joins `Pages`/`Page`/`Line` with `cardinality: many` (default EXISTS, no row multiplication, so the duplicate-row problem is fixed). Operators from the old advanced list via `options.operators`.
- **B (available now)**: `native` builder emitting one EXISTS (restrict to `contains` family; each operator maps to its own `LIKE`):

```php
return new Native('doctrine', static function (DoctrineQueryContext $q) use ($text): string {
    $p = $q->parameter('%'.AbstractTextCompiler::escape(mb_strtolower($text))."%");
    return "EXISTS (SELECT 1 FROM Quarks\\SdsAnalysis\\PageBundle\\Entity\\Pages fp "
         . "JOIN Quarks\\SdsAnalysis\\PageBundle\\Entity\\Page fpg WITH fpg.pages = fp.id "
         . "JOIN Quarks\\SdsAnalysis\\PageBundle\\Entity\\Line fl WITH fl.page = fpg.id "
         . "WHERE fp.sdsFile = {$q->rootAlias()} AND LOWER(fl.text) LIKE {$p} ESCAPE '!')";
});
```
Use unique alias prefixes (`fp`, `fpg`, `fl`); `AbstractTextCompiler::escape` is `public static` (`dfb:Compiler/AbstractTextCompiler.php:52`). Behaviour change: duplicates disappear (intentional). Non-contains advanced operators (starts/ends/equals/not_*) are not carried unless the builder implements them.

#### 2.3.6 `WorkspaceFilterType` (`workspaces`, 6 usages): security-dependent runtime default

v6 (`WorkspaceFilterType.php:25-48`, extends `ChoiceFilterType`):
- non-admin (`!isGranted('ROLE_ADMIN')`) **and** value `null` or empty `Countable` -> value replaced by `workspaceManager->findByCurrentUser()` (`:27-31`): a non-admin can **never clear** the filter;
- `isEmpty()` returns `false` for non-admins (`:41-48`): the filter always applies for them;
- `getChoices()` = current user's workspaces (`:36-39`);
- admin and empty: no filter.
- A non-admin can submit any workspace the form accepts (the form type `WorkspaceSelectorType`, `filters.yaml:35-43`, restricts choices; v6 does not intersect) .
Attributes: `sdsFileWorkspaces.workspace` / `page.pages.sdsFile.sdsFileWorkspaces.workspace` / `sdsFile.sdsFileWorkspaces.workspace` / `sdsFileAnalysisValidation.sdsFile.sdsFileWorkspaces.workspace` (all through the to-many `sdsFileWorkspaces`, `SdsFile.php:65`, ending on the association `workspace`). In v7 each is **one correlated EXISTS** (`dfb:Query/FieldAccess.php:85-115`), but **`Equals`/`In` on a path that ends on an association is refused by the Doctrine bundle** (`dfb:Metadata/FieldCapabilities.php:40-42,89-110`: "ends on an association: compare one of its fields (e.g. path.id)"). Every `workspaces` attribute must therefore end on the identifier: `...sdsFileWorkspaces.workspace.id` (assuming `Workspace` has a single `id`, entity not read). The same rule applies to every qk attribute that ends on a relation and is used with `choice`/`exact`/`custom_choice` (`SdsFileLanguage.language`, `sds.language`, `page.pages.sdsFile` in `SdsFileInnerLines.yaml:12`, `language`); `IsNull` on a to-one association end is accepted (`FieldCapabilities.php:98-104`), on a collection it is refused ("never null, use is_empty").

v7 mechanism: `locked` is **not** usable (static `default`, `docs/configuration.md`: "locked requires a default"). `default:` is static too. The hook is the filter type itself: **`normalize()` runs for every filter on every request, also with `null` and `''`** (`Provider/AbstractQueryHandler.php:170-176`: `raw = default`, replaced by the input when not locked and not null; `normalize($filter, $raw)` is always called), and instance methods may use injected services (`docs/filter-types.md` "Writing your own type"). `DefaultsWarmer` only normalises configured defaults (`Definition/DefaultsValidator.php:26` skips `null`), so there is no security call without a request at warm-up (do not configure `default:` on this filter, `isGranted` would throw without a token).

```php
final class WorkspaceFilterType extends AbstractFilterType     // autoconfigured; constructor autowired
{
    public function __construct(
        private readonly WorkspaceManager $workspaceManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public static function getName(): string { return 'workspaces'; }
    public static function configureOptions(OptionsResolver $resolver): void {}
    public static function getRequiredOperations(FilterDefinition $filter): array { return self::withAnyOf($filter, [In::class]); }

    public function normalize(FilterDefinition $filter, mixed $raw): NormalizedValue
    {
        $allowed = array_map(static fn (Workspace $w) => $w->getId(), $this->workspaceManager->findByCurrentUser());
        $admin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
        if (self::isBlank($raw)) {
            return $admin ? NormalizedValue::empty() : NormalizedValue::of($allowed);   // v6 :27-31, :41-47 (non-admin: always restricted, even with no workspace)
        }
        $ids = array_values((array) $raw);
        if (!$admin && [] !== ($foreign = array_diff($ids, $allowed))) {
            return NormalizedValue::invalid(new Violation('', 'Unknown workspace.', $foreign));       // NEW: v6 did not intersect
        }
        return NormalizedValue::of($ids);
    }

    public function buildCriterion(FilterDefinition $filter, mixed $value): ?Criterion
    {
        return $this->forAttributes($filter, static fn (Field $f) => new In($f, $value));
    }
}
```

Points:
- **Empty allow-list for a non-admin**: v6 passes an empty collection to the choice filter and returns nothing (`WorkspaceFilterType.php:30` + `isEmpty` false, `:43-45`). The skeleton therefore returns `NormalizedValue::of($allowed)` even when `$allowed` is `[]` for non-admins: `In` with no values compiles to `1 = 0` (`dfb:Compiler/InCompiler.php:25-28`, `DqlExpression::never()`), i.e. nothing is shown (fail-closed). Returning `empty()` there would show **everything**: add a test for a non-admin with zero workspaces.
- Invalid input falls back to `normalize($filter, $filter->default)` = `normalize(null)` (`AbstractQueryHandler.php:178-186` region) => non-admins get their workspaces again: fail-closed. Good.
- Cost: `findByCurrentUser()` runs on every list request; cache inside the manager if it hits the DB (the v6 code called it only when blank).
- Form: `form: {type: WorkspaceSelectorType, options: {multiple: true}}` (`filters.yaml:35-43` `$formType`/`$formOptions`); the choices come from the form type; the value reaching `normalize` is the submitted ids (the form adapter's `toRaw`, `DESIGN.md` 18), not `Workspace` entities. `normalize()` receives **ids**, so `WorkspaceSelectorType` must be configured with `choice_value: id` / a data-transformer to scalars [INFERENCE, form type not read].
- It is a per-request-user service: it holds no state, safe in worker mode.

### 2.4 In-memory provider (`LogAnalyserBundle`)

v6 (`InMemoryQueryHandler.php`, `InMemoryQueryHandlerFactory.php`, 3 filter types): data set per request through `setInitialData()` (`LogAnalyserBundle/Action/ListAction.php:37`: `findAll()`; `ReadAction.php:39`: `read($logFile)`, a different data set per log file hash), filters mutate `filteredData` (`AbstractInMemoryFilterType.php:34`), `applySort` sorts `$this->data` (`InMemoryQueryHandler.php:46`) while the pager is built from `getFilteredData()` (`:71,87-93`), i.e. **the sort is never applied** (DESIGN 1.4 bug, confirmed by these lines; when no filter ran, `filteredData` is a copy of `data` taken at the first `getFilteredData()` call, after `applySort` ran only if sort was applied before: order of the pipeline decides, in the normal path filters run first).

v7: provider `in_memory` (core): list options `source` (service id of an `InMemorySourceInterface`) and `identifier` (`docs/providers.md`, `InMemory/InMemoryQueryHandlerFactory.php:80-83`); sort is a real comparator with stable id tie-break (fixes the bug), types are the core `text`/`choice`/`date_range`. The fork factory (`provider: in_memory` via tag `sidus.query_handler_factory`), `InMemoryQueryHandler(+Interface)`, `AbstractInMemoryFilterType`, `AdvancedTextFilterType`, `ChoiceFilterType`, `DateRangeFilterType` and their registrations (`LogAnalyserBundle/Resources/config/services.yaml:13-34`) are all deleted; the three type names `advanced_text`, `date_range`, `choice` are registered in v6 for the in-memory provider while AdminBundle registers the same names for doctrine (`filters.yaml`), a clash that disappears.

```yaml
# AFTER  LogLine.yaml (inline query_handler block; AFTER LogLine.yaml:5-30)
query_handler:
    provider: in_memory
    options: { source: Quarks\SdsAnalysis\LogAnalyserBundle\Source\LogLineSource }   # `identifier` defaults to `id` (InMemoryQueryHandlerFactory.php:82); monolog records have no `id`: set `identifier: datetime` or similar [INFERENCE: behaviour of a missing tie-break field not read]; see O5
    filters:
        sort:
            type: sort
            options:
                columns: { datetime: datetime, level_name: level_name, hash: extra.hash }
        datetime: { type: date_range }
        level_name:
            type: choice
            options: { choices: { EMERGENCY: EMERGENCY, ALERT: ALERT, CRITICAL: CRITICAL, ERROR: ERROR, WARNING: WARNING, NOTICE: NOTICE, INFO: INFO, DEBUG: DEBUG } }
        message: { type: text }
        hash: { type: text, attributes: [ extra.hash ] }
```

(`LogLine.yaml:7-30` BEFORE; the sort keys cannot be `extra.hash` as public name if a dot is a problem for form names [INFERENCE], hence the key `hash`.)

`LogFile.yaml:40-48`: `options.entity: ...LogFile`, `sortable: {}`, `filters: {}`: in v7 `options` has `source` instead of `entity`, and `columns` must not be empty (a `sort` filter with `columns: {}` is refused: `Filter/Type/SortFilterType.php:43-47` "if [] === $columns" branch), so **omit the sort filter** entirely; the list has no filters at all.

```php
final class LogFileSource implements InMemorySourceInterface
{
    public function __construct(private readonly LogFileManager $logFileManager) {}
    public function items(): iterable { return $this->logFileManager->findAll(); }      // ListAction.php:37 (iterator_to_array there, not needed)
}
```

**Per-request data (`ReadAction`) is a real gap**: `InMemorySourceInterface::items()` takes **no argument** (`InMemory/InMemorySourceInterface.php`), the source is a shared container service referenced by id (`InMemoryQueryHandler.php:30`: `$this->source->items()`), and there is no `withSource()`/per-request data hook on the in-memory handler. The data set of `ReadAction` depends on the route parameter `hash` (`ReadAction.php:30-39`). Workaround: a source that reads the current request (`RequestStack::getCurrentRequest()->attributes->get('hash')`) and calls `LogFileManager::read()`; it is stateless (state is the request, not the object), thus worker-safe, but couples the source to the HTTP layer (CLI use impossible). [INFERENCE: `RequestStack` approach] Open question O5 for the core owner: should `InMemoryQueryHandler` accept `withItems(iterable)` per request? It would remove this workaround and is the in-memory counterpart of `withCustomizer()`.

`LogLine` rows are monolog arrays with `datetime` (DateTimeInterface or string) and nested `extra.hash`: the in-memory reference reads nested keys and "an ISO string meets a date" (`DESIGN.md` 18 "In-memory provider"), covering the v6 `new DateTime($itemValue)` fallback (`DateRangeFilterType.php:35-37`). `date_range` input shape changes (`startDate/endDate` -> `from/to`, `UPGRADE-7.0.md`), also inclusive upper bound (v6 compares `> $endDate`, `:43`, a date-only end meant midnight; v7 `to` = last microsecond of the day).

### 2.5 Other qk custom code that reaches into the handler

#### 2.5.1 `SdsFile/ListAction.php` (inner-lines workaround)

`PageBundle/Action/SdsFile/ListAction.php:78-109` loops the SdsFile page, and for each file clones the `SdsFileInnerLines` QueryBuilder (`:87,93`), calls `setQueryBuilder($qb, 'e')` (`:94`) and runs every filter type's `handleData()` by hand with the **main form's data** (plus a special case `sdsFile` = the entity, `:142-144`), then `toIterable()` all lines (`:105`) with no pagination. All of `getQueryBuilder`, `setQueryBuilder`, `getConfiguration()->getFilters()`, `FilterTypeRegistry::getFilterType(provider, type)`, `handleData` and `FilterInterface::getDefault/getCode` are gone in v7.

v7 shape: build the lines input from the **raw submitted input** of the main list, since input for names the list does not have is ignored (`AbstractQueryHandler.php:166-183` iterates the definition's filters only; the old "filters with the same names must be compatible" warning, `:76`, still holds for names that exist in both lists):

```php
$input = $listFormFactory->submit($form, $request->query->all());          // the main list's ListInput
foreach ($result->items() as $sdsFile) {
    $lineInput = new ListInput(
        filters: [...$input->filters, 'sdsFile' => $sdsFile->getId()],      // the 'sdsFile' filter is `exact` (SdsFileInnerLines.yaml:11-13)
        page: 1,
        perPage: 1000,                                                      // see risk
    );
    $lines[$sdsFile->getId()] = $registry->createQueryHandler('SdsFileInnerLines')->handle($lineInput)->items();
}
```

Notes: `createQueryHandler()` returns a **new single-use** handler per call (that was the whole point of cloning the builder); `per_page` is bounded by `max_per_page` (`docs/configuration.md` "Pagination"): set `pagination: { per_page: 1000, max_per_page: 5000 }` on `SdsFileInnerLines` (the old code had **no limit**, `toIterable()` of everything: behaviour change; open question O6: is a cap acceptable, or does the core need an unbounded iteration API?); `count: none` (no COUNT needed). `exact` takes a scalar: pass the **id**, not the entity (v6 passed the entity, `:143`). The `default:` handling for non-submitted sub-forms (`:149-152`, "submit the default data so model transformers apply") disappears: v7 normalises defaults in the filter type. `SdsFileInnerLines.yaml:8-9` `default_sort: {id: ASC}` => `sort` filter with a column `id` and `default`.

And `SdsFile/ListAction.php:20-22,45-46` inject `FilterTypeRegistry`/`DataGridRegistry` solely for this: remove.

#### 2.5.2 `SdsFileValidationStatistics/ListAction.php`: runtime-generated sort and filters

`:47-52,82-113` mutates the data grid per request: adds columns, `getConfiguration()->addSortable("statistics_<cat>")` and `new Filter(...)` objects (`filterType: 'advanced_number'`, attributes `statistics-><cat>->regression::int`/`...->unknown::int`) from `quarks.validation.config`. v7 definitions are immutable and compile-time (`UPGRADE-7.0.md`: "`add*`/`unset` removed"). Generate the list config at container build time, e.g. `config/packages/sidus_filter_validation.php` (the categories live in `config/admin/validation.yaml:2`, plain YAML):

```php
return static function (ContainerConfigurator $c): void {
    $categories = Yaml::parseFile(__DIR__.'/../admin/validation.yaml')['parameters']['quarks.validation.config'];
    $filters = $columns = [];
    foreach (array_keys($categories) as $cat) {
        $columns['statistics_'.$cat] = ['field' => 'statistics', 'provider_options' => ['doctrine' => ['expression' => '...{root}...'/* 2.2.3 */]]];
        foreach (['regression', 'unknown'] as $kind) {
            $filters['statistics'.ucfirst($cat).ucfirst($kind)] = [
                'type' => 'number',
                'attributes' => [['path' => 'statistics', 'json' => [$cat, $kind], 'cast' => 'integer']],
                'options' => ['operators' => ['eq', 'neq', 'gt', 'gte', 'lt', 'lte']],
            ];
        }
    }
    // merged into the SdsFileValidationStatistics list (sort columns + filters)
    $c->extension('sidus_filter', ['lists' => ['SdsFileValidationStatistics' => ['filters' => $filters + ['sort' => ['type' => 'sort', 'options' => ['columns' => $columns]]]]]]);
};
```

Caveat: the static `SdsFileValidationStatistics.yaml` already declares a `sort` filter; two config sources defining the same filter key are merged as arrays by the config tree (merge strategy of `columns` maps is [INFERENCE: normally additive for maps keyed by name]). Safer: declare **only** the generated `sort` filter in the PHP file and keep the YAML free of it. The columns/keys of the existing static list (`sdsFile.filename`, `productName`, `supplierName`, `language.code`, `revisionDate`, `sdsFile.analysisDate`, `sdsFile.analysisDuration`, `total`: `SdsFileValidationStatistics.yaml:12-20`) go in the same map. The data grid *columns* (`addColumn`, `:83-91`) are a DataGrid concern (DataGrid section). Blocked by B1/B3 for the JSON filters and expression sort.

### 2.6 Representative real list configs, BEFORE -> AFTER

#### `Line` (`PageBundle/Resources/sidus_data_grid/Line.yaml`)

```yaml
# BEFORE (:5-30 excerpt)
query_handler:
    provider: doctrine
    results_per_page: 50
    options:
        entity: Quarks\SdsAnalysis\PageBundle\Entity\Line
        joins:
            Quarks\SdsAnalysis\LanguageFinderBundle\Entity\SdsFileLanguage:
                alias: SdsFileLanguage
                condition: [ sdsFile, page.pages.sdsFile ]
    filters:
        filename: { attributes: [ page.pages.sdsFile.filename ], type: advanced_text }
        workspaces: { attributes: [ page.pages.sdsFile.sdsFileWorkspaces.workspace ], type: workspaces }
        noLanguage: { attributes: [ SdsFileLanguage.language ], type: is_null }
        language:
            attributes: [ SdsFileLanguage.language ]
            type: custom_choice
            form_type: Symfony\Bridge\Doctrine\Form\Type\EntityType
            form_options: { class: Quarks\AnalysisBundle\Entity\Language, multiple: true, choice_translation_domain: messages, attr: { class: select2 } }
        analysisDate: { attributes: [ page.pages.sdsFile.analysisDate ], type: date_range }
        subsectionNumber: { attributes: [ sectionReference.subsectionNumber ], type: advanced_number }
        header: { type: choice, form_options: { choices: { Yes: 1, No: 0 } } }
        regexp: { attributes: [ text ], type: regexp }
        lineNumber: { type: advanced_number }
# AFTER
query_handler:
    provider: doctrine
    pagination: { per_page: 50 }
    count: none                                  # replaces allowOutOfRangePages + setNbResults(-1), Line/ListAction.php:38,56-61
    options:
        entity: Quarks\SdsAnalysis\PageBundle\Entity\Line
        joins:                                   # blocked by B2
            SdsFileLanguage:
                entity: Quarks\SdsAnalysis\LanguageFinderBundle\Entity\SdsFileLanguage
                on: { field: sdsFile, equals: page.pages.sdsFile }
                cardinality: one
    filters:
        sort: { type: sort, options: { columns: { id: id }, default: [ { column: id, direction: asc } ] } }   # Line.yaml has no sortable/default_sort: see O7
        filename: { attributes: [ page.pages.sdsFile.filename ], type: text, options: { operators: [contains, not_contains, starts_with, ends_with, equals] } }   # was advanced_text; operator list = old advanced_text_choices [INFERENCE: parameter value not found]
        workspaces: { attributes: [ page.pages.sdsFile.sdsFileWorkspaces.workspace.id ], type: workspaces, form: { type: Quarks\SdsAnalysis\SdsFileBundle\Form\Type\WorkspaceSelectorType, options: { multiple: true } } }
        noLanguage: { attributes: [ SdsFileLanguage.language ], type: 'null' }
        language:
            attributes: [ SdsFileLanguage.language.id ]
            type: choice                         # was custom_choice; values are ids; `.id` leaf required (association-end In is refused, section 0); no `relation` type in the Doctrine bundle yet
            options: { multiple: true }
            form: { type: Symfony\Bridge\Doctrine\Form\Type\EntityType, options: { class: Quarks\AnalysisBundle\Entity\Language, multiple: true, choice_translation_domain: messages, attr: { class: select2 } } }
        analysisDate: { attributes: [ page.pages.sdsFile.analysisDate ], type: date_range }
        subsectionNumber: { attributes: [ sectionReference.subsectionNumber ], type: number, options: { operators: [eq, neq, gt, gte, lt, lte] } }
        header: { type: choice, options: { choices: { Yes: 1, No: 0 } } }
        regexp: { attributes: [ text ], type: regexp }
        lineNumber: { type: number, options: { operators: [eq, neq, gt, gte, lt, lte] } }
```

`Line.yaml` has no `sortable`/`default_sort` (v6 sorting was thus only the columns declared by `columns: [ ]`, `:97`): no sort filter is required unless columns become sortable [O7]. `type: 'null'` is quoted in YAML.

`sectionNumber` with `type: custom_choice` + `SectionNumberType` (`Line.yaml:47-52`) -> `choice` + `form.type`, same pattern. `filters.language` on an **association** with `multiple: true`: `In` against association ids; requires `FieldCapabilities` to accept a to-one association end for `In` [INFERENCE, not verified].

#### `SdsFileValidationStatistics` (`ValidationBundle/Resources/sidus_data_grid/SdsFileValidationStatistics.yaml`)

```yaml
# BEFORE (:9-33 excerpt)
default_sort: { sdsFile.hash: ASC }
results_per_page: 20
sortable: [ sdsFile.filename, productName, supplierName, language.code, revisionDate, sdsFile.analysisDate, sdsFile.analysisDuration, total ]
filters:
    totalRegressions: { attributes: [ totals->regression::int ], type: advanced_number }
    totalUnknowns: { attributes: [ totals->unknown::int ], type: advanced_number }
    hash: { attributes: [ sdsFile.hash ], type: hash }
    workspaces: { attributes: [ sdsFile.sdsFileWorkspaces.workspace ], type: workspaces }
# AFTER
pagination: { per_page: 20 }
filters:
    # `sort` is generated (2.5.2) because it needs the statistics_<category> expression columns
    totalRegressions: { type: number, attributes: [ { path: totals, json: [regression], cast: integer } ], options: { operators: [eq, neq, gt, gte, lt, lte] } }   # blocked by B1
    totalUnknowns:    { type: number, attributes: [ { path: totals, json: [unknown],    cast: integer } ], options: { operators: [eq, neq, gt, gte, lt, lte] } }   # blocked by B1
    hash: { type: hash, attributes: [ sdsFile.hash ] }                                       # custom type of 2.3.1
    workspaces: { type: workspaces, attributes: [ sdsFile.sdsFileWorkspaces.workspace.id ] }   # custom type of 2.3.6; `.id`: Equals/In on an association end is refused (section 0)
```

Static sort columns to place in the generated `sort` filter: `sdsFile.filename`, `productName`, `supplierName`, `language.code`, `revisionDate`, `sdsFile.analysisDate`, `sdsFile.analysisDuration`, `total`; default `[{column: sdsFile.hash, direction: asc}]` — the column **key** `sdsFile.hash` must exist in `columns` (`SortFilterType.php:67-69` throws at compile time otherwise) even though v6 listed it only in `default_sort`. Note also v6 columns reference `sort_column: sdsFile.filename` (`:87`): public keys in v7.

#### `SdsFileContent` / `SdsFile` (extracts)

`SdsFile.yaml:28-36`: `fullTextSearch: {type: sds_file_text}` -> custom (2.3.5); `hash`, `workspaces` as above; `revisionDate`/`noRevisionDate` via named join `SdsFileRevisionDate` (blocked B2, `reference: sdsFile` spelling O1); `sortable: [createdAt, filename, sds.product.name, sds.product.code, sds.product.supplier.name, sds.language, sds.revisionDate, contentSize, analysisDate]` (`SdsFile.yaml:15-24`) -> `sort.columns` with identifier keys (e.g. `productName: sds.product.name`); `default_sort: analysisDate: DESC` -> `default: [{column: analysisDate, direction: desc}]`.

### 2.7 Twig and glue

- `AdminBundle/Resources/views/Macros/utilities.html.twig:26-28`: `datagrid.queryHandler.configuration.sortable` loop + `form_widget(datagrid.formView.sortable[index], {label})` -> iterate the sort filter's columns (`datagrid.definition.sort.columns`, `DESIGN.md` 14) and render `datagrid.formView.sort[key]` / `filters.<sort>.vars.columns[key]` with button `name = full_name`, `next` value.
- `ValidationBundle/Resources/views/datagrid/validation.html.twig:80` `datagrid.formView.sortable` -> `datagrid.formView.sort`.
- Translation keys `sidus.filter.combo.*` (parameter `sidus.filter.advanced_text_choices`, used at `filters.yaml:50`/`LogAnalyser services.yaml:19`, **defined by the v5 bundle**, not found in the repo) disappear with the combo option codes: replaced by `sidus.filter.operator.<operator>`.
- Remove `sidus.query_handler_factory` tag usage (`AdminBundle/Resources/config/services.yaml:15-17`) and the v6 `sidus.filter_type` tag registrations with `$name`/`$formType` arguments (`filters.yaml`, `Section9Bundle/Resources/config/filters.yaml`, `ValidationBundle/Resources/config/services.yaml:19`, `LogAnalyserBundle/Resources/config/services.yaml`): v7 types are autoconfigured and named by `getName()`.

### 2.8 qk checklist, ordered by dependency

1. composer: `sidus/filter-bundle ^7.0`, `sidus/doctrine-filter-bundle`, datagrid 5.x; PHP 8.4/Symfony 8.1 requirements (`UPGRADE-7.0.md`): qk's own constraint is not checked here.
2. **Wait for / drive Doctrine-bundle features B1 (JSON paths + `JsonPathCompilerInterface`), B2 (named joins incl. `shared`, chain, root reference), B3 (expression sort, multi-key)**. Everything below that does not depend on them can proceed.
3. Delete the fork: `Query/Handler/DoctrineQueryHandler.php`, `Query/Factory/DoctrineQueryHandlerFactory.php`, tag `AdminBundle/Resources/config/services.yaml:15-17` (two `doctrine` providers is a compile error).
4. Port custom types in this order: `NullFilterType`/`NotEmptyFilterType`/`RegexpFilterType` -> core types (just delete); `WorkspaceFilterType` (2.3.6, security-critical); `HashFilter` (criterion + compiler, 2.3.1); `UnitCategory`/`ValidationCategory` natives (2.3.4); `SdsFileTextFilter` (2.3.5 B now, A later).
5. Rewrite the 33 list YAMLs mechanically (`advanced_text` 77 -> `text` + `operators`; `advanced_number` 41 -> `number` + `operators`; `custom_choice`/`choice` -> `choice`; `is_null` -> `null`; `form_type`/`form_options`/`label` -> `form`; `sortable`/`default_sort` -> `sort` filter; `results_per_page` -> `pagination.per_page`); a script over `grep`-ed patterns is feasible, `choice.form_options.choices` -> `options.choices`.
6. In-memory: add `LogFileSource`/`LogLineSource` (2.4), delete the LogAnalyser handler/factory/types, rewrite `LogLine.yaml`/`LogFile.yaml`, adapt `ListAction`/`ReadAction` (drop `setInitialData`).
7. Port the 4 actions (2.5), generated validation config (2.5.2).
8. Twig macros and templates (2.7), translations.
9. `cache:warmup` (invalid defaults/choices/sort keys/capabilities fail here), `debug:sidus-filter`; manual regression on: workspace isolation for a non-admin user with zero workspaces (must see nothing), hash prefix vs full-hash, regexp not-matching with nulls, statistics sort with `total = 0`, SdsFileContent same-line semantics.

**Deletable files (qk):** `AdminBundle/Query/Handler/DoctrineQueryHandler.php`, `AdminBundle/Query/Factory/DoctrineQueryHandlerFactory.php`, `AdminBundle/Filter/Type/{NullFilterType,NotEmptyFilterType,RegexpFilterType,SdsFileTextFilter(after option A)}.php`, `AdminBundle/Resources/config/filters.yaml` (after the replacements are registered), `LogAnalyserBundle/Query/Handler/InMemoryQueryHandler(Interface).php`, `LogAnalyserBundle/Query/Factory/InMemoryQueryHandlerFactory.php`, `LogAnalyserBundle/Query/Filter/Type/*.php`, the type registrations in `LogAnalyserBundle/Resources/config/services.yaml:13-34`. **Stays (rewritten):** `HashFilter` (as `HashFilterType`), `WorkspaceFilterType`, `UnitCategoryFilterType`, `ValidationCategoryFilterType`, all list YAML, the app DQL function registrations (`CAST`, `ENCODE`, `ILIKE`, `JSON_GET_*`, `JSONB_EXISTS`), `SdsFile/ListAction` and `Validation/ListAction` (rewritten).

**Risks / open questions (qk):**
- B1/B2/B3 (Doctrine-bundle gaps, section 0): JSON filters, named joins, computed sort cannot ship until implemented; `joins` is currently silently accepted and ignored (`DoctrineQueryHandlerFactory.php:67`), so a migrated config would **parse and then fail with unresolved paths at warm-up** rather than clearly: ask for a compile error on unimplemented options.
- O1: spelling of `reference: sdsFile` (`alias.field = root`) in `joins.on`.
- O2: named join whose `equals` targets another named join (`condition: [pages, Pages.id]`, `SdsFileContent.yaml:17-21`) and the nested EXISTS compilation of a chain Pages -> Page -> Line.
- O3: sort on an expression with several order keys (v6 three keys, `:195-197`).
- O4: does `form.type` produce a field for `native` filters (no mapper)?
- O5: per-request in-memory data set (ReadAction by hash): `withItems()` or RequestStack-based source.
- O6: unbounded line fetch in `SdsFile/ListAction` vs `max_per_page`.
- O7: `Line.yaml`/other lists without `sortable`: confirm no sort wanted; `columns:` of the grid then must not rely on sort.
- Behaviour changes to announce: regexp POSIX-subset only, `not_matches` includes nulls, hash invalid input is a violation, workspace foreign ids are a violation, full-text search no longer duplicates rows, sort null ordering flips (nulls first ascending), advanced operators beyond the shipped `text`/`number` operator lists are not available.
- qk's lock is filter 5.0.2 / datagrid 4.0.0 while keres is 6.0.3 / 4.0.2: they cannot share one datagrid-bundle 5.0 upgrade unless both are moved at once [INFERENCE].
