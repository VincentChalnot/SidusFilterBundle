# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Fixed

- Symfony 8 compatibility: `QueryHandlerRegistry` and `FilterTypeRegistry` used the `#[TaggedLocator]` and
  `#[TaggedIterator]` attributes, removed in Symfony 8.0 (autowiring then failed with "references class
  ServiceLocator but no such service exists"). They now use `#[AutowireLocator]` and `#[AutowireIterator]`
  (available since Symfony 6.4). `defaultIndexMethod: 'getProvider'` is kept so that 6.4 and 7.4 behave as
  before; it is deprecated by Symfony 8.1 (`#[AsTaggedItem]` replaces it), a deprecation only.

### Changed

- The `symfony/*` requirements are now `^6.4 || ^7.4 || ^8.0` instead of `>=6.3`: the Autowire attributes
  need 6.4, only the LTS lines and the current major are supported, and Symfony 9 is not claimed.

## [6.0.3] - 2026-07-21

### Fixed

- PHP 8.4 deprecates typed parameters defaulting to `null` without an explicit `?` prefix.
  Fixed in `QueryHandlerConfiguration(Interface)` and `SortConfig`.

### Note

- The above fix was originally released as `6.0.2`. Its tag was mistakenly force-moved after
  publication (a `git commit --amend` to add this changelog section, then a re-tag) to attach
  this entry, which Packagist correctly rejected: stable tags are immutable once published.
  `v6.0.2` has been restored to its originally-published commit (`3df11a6`, no changelog entry
  for this fix), and this changelog entry is released as `6.0.3` instead. No code changed
  between `6.0.2` and `6.0.3` - `6.0.3` only adds this section.

## [6.0.1] - 2026-07-21

### Fixed

- `AbstractSimpleFilterType::requiresCaseInsensitiveLikeTransform()` called the removed
  `Doctrine\DBAL\Platforms\AbstractPlatform::getName()` method (dropped in `doctrine/dbal` 4.0),
  crashing every `LIKE`/`NOT LIKE` filter (e.g. the built-in text filter) with `Call to
  undefined method ...::getName()` on any DBAL 4 project. Replaced with an `instanceof
  Doctrine\DBAL\Platforms\PostgreSQLPlatform` check.
- Declared the previously-implicit `doctrine/dbal` and `doctrine/orm` dependencies in
  `composer.json` (the `Doctrine\` namespace was used throughout `Doctrine/` without either
  package being required).

## [6.0.0] - 2026-07-21

### Changed (BC breaks)

- Requires `doctrine/orm` `^3.0`. The bundle now relies directly on Doctrine ORM's own
  `Doctrine\ORM\Tools\Pagination\Paginator` instead of a custom fork.
- Removed `Sidus\FilterBundle\Pagination\DoctrineORMPaginator` and
  `Sidus\FilterBundle\Pagination\CountOutputWalker`. These classes existed only to work around
  bugs in old Doctrine ORM 2.x output walkers/pagination that are no longer present in ORM 3.
  If you depended on these classes directly, switch to `Doctrine\ORM\Tools\Pagination\Paginator`.
- `Sidus\FilterBundle\Pagination\DoctrineORMAdapter` now type-hints against
  `Doctrine\ORM\Tools\Pagination\Paginator` instead of the removed custom paginator.
- `Sidus\FilterBundle\Doctrine\Metadata\DoctrineAttributeMetadataResolver` and
  `Sidus\FilterBundle\Doctrine\Filter\Type\EntityFilterType` no longer reference the removed
  `Doctrine\ORM\Mapping\ClassMetadataInfo` (merged into `Doctrine\ORM\Mapping\ClassMetadata` in
  ORM 3) or the removed `Doctrine\ORM\Mapping\MappingException`.

### Compatibility

- Verified against PHP 8.2-8.4 and Symfony 7.4.
- No changes required for consumers who only use the bundle's public configuration/service API
  (`sidus_filter` config, `QueryHandlerRegistry`, `FilterFactory`, Twig form themes).

### Note

- `v6.0.0`'s tag was also mistakenly force-moved after publication (forward onto the commit that
  added this changelog file), which Packagist rejected the same way. Restored to its
  originally-published commit (`4020dfb`, predating this file); the changelog content itself is
  still available starting at `6.0.1`, which already carries it as an ancestor.

## [5.0.2] - 2024-03-26

See git history prior to this file for changes up to 5.0.x.
