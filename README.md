# Sidus/FilterBundle

Provider-agnostic list filtering, sorting and pagination for Symfony 8.1+ (PHP 8.4+).

A *list* is configured once (filters, sorting, pagination, count strategy) and means the same thing whatever the data
source: an in-memory array today, Doctrine ORM, EAV, Elasticsearch or a remote API through a provider package.
Raw input (query string, JSON body, an HTML form) goes in; a page of items, a lazy total and a list of violations
come out.

- **Request isolation by construction.** The container only holds immutable objects. Everything that holds a query, a
  page, a form or a join map is created per request: `QueryHandlerRegistry::createQueryHandler()` returns a new,
  single-use handler on every call, which makes the bundle safe in worker mode (FrankenPHP, RoadRunner).
- **Strict.** A provider either realises a criterion with the reference meaning or refuses it; a list that asks for
  something its provider cannot do fails when the container compiles, never on a visitor's request.
- **No Symfony Form required.** The core takes a plain array. The Form, HTTP and Pagerfanta adapters are optional.

7.0 is a clean break from 6.x: see [UPGRADE-7.0.md](UPGRADE-7.0.md). The Doctrine ORM provider moved to
`sidus/doctrine-filter-bundle`. The accepted design is in [docs/DESIGN.md](docs/DESIGN.md).

## Installation

```bash
composer require sidus/filter-bundle
```

Optional adapters: `symfony/form` (HTML forms and themes), `pagerfanta/core`, `symfony/console` (`debug:sidus-filter`),
`symfony/translation` and `symfony/twig-bridge` (translated, themed forms). Without a Symfony Flex recipe, register
`Sidus\FilterBundle\SidusFilterBundle` in `config/bundles.php`.

## Quick start

```yaml
# config/packages/sidus_filter.yaml
sidus_filter:
    lists:
        posts:
            provider: in_memory
            options: { source: App\Posts\PostsSource }       # an InMemorySourceInterface service
            pagination: { per_page: 15, max_per_page: 100 }
            filters:
                title: { type: text, options: { operators: [contains, starts_with] } }
                author: { type: text, attributes: [author.name, author.email] }   # OR over the attributes
                status: { type: choice, options: { choices: [draft, published] }, default: published, locked: true }
                published: { type: date_range, attributes: [publishedAt] }
                sort:
                    type: sort
                    options:
                        columns: { title: title, date: publishedAt, author: author.name }
                        default: [{ column: date, direction: desc }]
```

```php
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;

$handler = $registry->createQueryHandler('posts');                  // a new object on every call
$result = $handler->handle(ListInput::fromArray($request->query->all()));   // single use

$result->items();       // list<mixed>, one page
$result->page();        // 1-based
$result->total();       // lazy, computed once on first read; null with count: none
$result->violations();  // what in the input was invalid: that part fell back to its default
$result->state();       // the normalised, applied state
```

An API endpoint with the HTTP adapter:

```php
public function __invoke(#[MapListInput('posts')] ListInput $input, QueryHandlerRegistry $registry, ListResponseFactory $responses): Response
{
    return $responses->create($registry->createQueryHandler('posts')->handle($input), $itemNormalizer);
}
```

`bin/console debug:sidus-filter [code]` lists the lists, or shows one with its active filters and the criteria each
can emit.

## Documentation

- [Configuration](docs/configuration.md)
- [Filter types](docs/filter-types.md)
- [Forms, themes and HTTP/API](docs/adapters.md)
- [Writing a provider (SPI)](docs/providers.md) and the [contract test kit](docs/contract-kit.md)
- [Upgrading from 6.x](UPGRADE-7.0.md), [design](docs/DESIGN.md), [changelog](CHANGELOG.md)

## Tests

```bash
composer install && vendor/bin/phpunit
```
