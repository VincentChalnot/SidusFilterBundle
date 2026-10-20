# Filter types

A filter type validates and normalises the raw input of one filter and emits abstract *criteria*. It knows nothing about
data sources or forms. Operators are named (`contains`, `not_starts_with`, `gte`...) and listed in `options.operators`;
the first one is the default for a bare value.

| Type | Input | Options | Emits |
|---|---|---|---|
| `text` | string, or `{operator, value}` | `operators` (default `[contains]`), `case_sensitive` (default `false`) | `Contains`, `StartsWith`, `EndsWith`, `Equals`, `IsNull`, `IsEmpty`, each possibly inside `Not` |
| `exact` | scalar | | `Equals` |
| `choice` | scalar, or a list with `multiple: true` | `choices` (list, or map label => value), `multiple` | `Equals`, `In` |
| `number` | number, or `{operator, value}` | `operators` (default `[eq]`) | `Equals`, `Range`, `IsNull`, `Not` |
| `date_range` | `{from, to}`: ISO 8601 strings or `DateTimeInterface` | `timezone` (default `UTC`) | `Range` |
| `null` / `not_null` | boolean flag | | `IsNull` / `Not(IsNull)` |
| `not_empty` | boolean flag | | `Not(IsEmpty)` |
| `regexp` | string, or `{operator, value}` | `operators` (default: all four) | `Matches`, `Not(Matches)` |
| `sort` | `-date,title`, `[{column, direction}]`, `{column, direction}` or `{date: desc}` | `columns`, `default`, `max_columns` (default 3) | `Sort` |
| `native` | anything | `builder`: id of a `NativeCriterionBuilderInterface` service | `Native` |

Operators: **text** `contains not_contains starts_with not_starts_with ends_with not_ends_with equals not_equals is_null
is_not_null is_empty is_not_empty`; **number** `eq neq gt gte lt lte is_null is_not_null`; **regexp** `matches
not_matches imatches not_imatches`.

- `choice` without `choices` accepts any scalar. With `choices`, an unknown value is a violation, and the configured
  (typed) value is the one that is emitted.
- `date_range`: a date-only bound (`2026-10-20`) is interpreted in `timezone`; `from` is the start of that day, `to`
  the last microsecond of it. Both bounds are inclusive. `2026-02-30` is a violation, not a roll-over.
- `regexp` patterns must stay inside a portable POSIX ERE subset (`.`, `[]`, `*`, `+`, `?`, `{m,n}`, `^`, `$`, `|`,
  groups, `[[:digit:]]`): no `(?`, no `\d` or backreference, no possessive quantifier, at most 255 bytes. Matching is
  unanchored.
- `sort`: `columns` maps public keys to fields: `{ date: publishedAt, author: author.name }`, or per column
  `{ field: ..., provider_options: { doctrine: { ... } } }`. The input names keys, never property paths, so the
  allow-list is structural. `default` is a list of `{column, direction}` applied when the input has no valid sort.
  At most one sort filter per list. The provider appends the identifier as final tie-breaker; nulls sort first
  ascending and last descending.
- `native`: restrict it with `providers:` to the provider the builder targets.

## Criterion semantics (the reference is the in-memory provider)

- Strings are case-sensitive and compared byte-wise, numbers numerically, dates as instants. `Contains`,
  `StartsWith`, `EndsWith` fold case with `mb_strtolower` unless `case_sensitive`; accents are never folded; `%`, `_`
  and `*` are literal.
- **Null never matches a comparison** (`Equals`, `Contains`, `Range`, `Matches`), so `Not(Contains(x))` is true for null.
- **To-many fields mean "any element"**, and two criteria on the same to-many path are independent.
- `IsEmpty` is null, `''` or an empty collection; `IsNull` is null or absent (for a to-one relation: no related object).

## Writing your own type

Implement `Sidus\FilterBundle\Filter\FilterTypeInterface` (autoconfigured, one service per type). It has two halves:

- **static, compile time**: `getName()`, `configureOptions(OptionsResolver)` and `getRequiredOperations(FilterDefinition)`
  (every criterion class the filter can emit, composites included: this feeds the strict capability check). No service
  exists yet, so they are static and the compiler pass never instantiates your type.
- **instance, per request**: `normalize()` (raw input to a value, "empty" or violations; never throws on user input) and
  `buildCriterion()`. They may use injected services; the type may have constructor dependencies.

Configured `default:` values are checked by the cache warmer with the real instances, so an invalid default fails
`cache:warmup`. Compose the core criteria (`AnyOf(Contains(players.user.username), Contains(players.user.email))`) or
emit `Native`. Extend `AbstractFilterType` (or `AbstractOperatorFilterType`) for the helpers.
