# Configuration

```yaml
sidus_filter:
    lists:                         # by code; the code is what you pass to the registry
        posts:
            provider: doctrine     # required; known at compile time (a %parameter% is fine, an env var is not)
            options: { ... }       # validated by the provider (in_memory: source, identifier)
            pagination: { per_page: 15, max_per_page: 100 }
            count: auto            # auto | none | <id of a CountStrategyInterface service>
            filters:
                <key>:
                    type: text     # required: a filter type name (see filter-types.md)
                    name: ~        # public input name, defaults to the key; unique among the active filters
                    attributes: [] # dotted paths or {path, json, cast} maps; defaults to [<key>]
                    providers: []  # active only for these providers, ignored entirely for the others
                    options: {}    # validated by the filter type
                    provider_options: {}   # per provider name, validated by that provider, ignored by the others
                    locked: false  # the value is `default`, never read from the input
                    default: ~     # normalised like user input; invalid defaults fail at compile time
                    form: {}       # label, type, options: read by the form adapter only
    http:                          # request parser and response factory (HTTP adapter)
        filters_key: filters       # '' = every top-level parameter except the reserved ones is a filter
        sort_key: sort
        page_key: page
        per_page_key: per_page
        strict: true               # violations answer 400 application/problem+json
```

## Attributes

An attribute is a logical path from the list's own data model (`title`, `author.name`, `tags.label`), resolved by the
provider. Several attributes on one filter are combined with OR (`AnyOf`). For JSON documents use a map or the
shorthand: `{ path: totals, json: [regression], cast: integer }` is `totals->regression::integer`. Casts:
`integer`, `float`, `string`, `boolean`, `date` (`int` and `bool` are accepted aliases).

## Defaults, blank values and locked filters

- A filter **absent** (or `null`) in the input takes its `default`.
- An explicitly **blank** value (`''`, `[]`) clears the default: the filter is inactive.
- A **locked** filter always takes its `default` and ignores the input (it still goes through the filter type's
  normaliser, unlike v6 hidden filters). It requires a `default`.
- An **invalid** value is reported as a violation (`filters.<name>[.<sub-path>]`, `page`, `per_page`) and the filter falls
  back to its `default` (inactive without one). Normalisation never throws on user input.

## Pagination and count

`page` and `per_page` are positive integers (or digit strings); `per_page` is bounded by `max_per_page`.

| `count` | Total | `hasNextPage()` |
|---|---|---|
| `auto` | the provider's native count, lazy, once | derived from the total |
| `none` | `null` | exact: the handler fetches `per_page + 1` |
| a service id | what the `CountStrategyInterface` returns (it may return `null`) | derived from the total |

A total supplied with the input always wins: `$input->withTotalCount(1234)` or `->withTotalCount(fn () => $cache->get(...))`
(lazy, at most once).

## Several providers, one configuration

```yaml
provider: '%app.list_provider%'    # parameters: app.list_provider: elasticsearch (when@dev: doctrine)
filters:
    title_fulltext: { name: title, type: es_match, attributes: [title], providers: [elasticsearch] }
    title_like:     { name: title, type: text, attributes: [title], providers: [doctrine, in_memory] }
    status:         { type: choice, options: { choices: [draft, published] } }   # portable
```

A filter restricted with `providers:` is dropped before any check when the list's provider is not among them: no form
field, no API input, no capability check, no public-name clash.

## Compile-time validation

Everything that does not need a runtime service fails when the container compiles, with the list and filter in the
message: unknown provider or filter type, invalid options, invalid attribute paths, invalid defaults, `locked` without
`default`, duplicate public names, more than one sort filter, a `count` service that does not exist or does not implement
the interface, and **every criterion a filter can emit that the provider cannot realise** (narrow the filter with
`options.operators`, or restrict it with `providers:`).
