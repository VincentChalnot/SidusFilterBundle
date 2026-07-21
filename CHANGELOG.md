# Changelog

All notable changes to this project will be documented in this file.

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

## [5.0.2] - 2024-03-26

See git history prior to this file for changes up to 5.0.x.
