# The provider contract test kit

`Sidus\FilterBundle\Test\Contract` ships with the core (it is only autoloaded when you reference it, and needs
PHPUnit). It defines the behaviour every provider must show on the same data.

- `FixtureDataset`: authors, posts with a to-one author (or none), to-many tags, nulls, empty strings, mixed case,
  Unicode, wildcard characters (`%`, `_`, `*`), equal sort keys; plus the filters of the fixture list.
- `ContractCases`: the cases as data: input, expected ordered post ids, total, violation paths.
- `AbstractProviderContractTestCase`: runs every case, and checks single use, lazily supplied totals and the count
  strategies.

```php
final class MyProviderTest extends AbstractProviderContractTestCase
{
    protected function setUp(): void { /* load FixtureDataset::posts() into your storage */ }

    protected function providerName(): string { return 'my_provider'; }

    protected function listOptions(): array { return ['entity' => Post::class, 'identifier' => 'id']; }

    protected function createHandler(ListDefinition $definition, ?CountStrategyInterface $countStrategy = null): QueryHandlerInterface
    {
        return $this->factory->createQueryHandler($definition, FilterTypeRegistry::builtIn(), $countStrategy);
    }

    protected function itemId(mixed $item): int { return $item->getId(); }   // when items are not arrays with an `id`
}
```

## Known divergences

A platform limitation the suite finds (SQLite `LOWER()` folds ASCII only, a collation that is not byte order...) is
declared, never silently skipped:

```php
protected function knownDivergences(): array
{
    return ['contains_unicode_lowercase_fold_accented' => 'SQLite LOWER() is ASCII only'];
}
```

The suite asserts that a declared case **does** diverge, so a fixed limitation fails the test until the entry is
removed, and an entry naming a missing case fails too. Each entry must be fixed or turned into a capability refusal
before a release.
