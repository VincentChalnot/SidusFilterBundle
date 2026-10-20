# Adapters: forms, themes, HTTP, Pagerfanta

None of these is required by the core; each is registered only when its package is installed.

## Symfony Form (`symfony/form`)

```php
$form = $listFormFactory->create($registry->getDefinition('posts'));   // one form per request
$input = $listFormFactory->submit($form, $request->query->all());      // ListInput
$result = $registry->createQueryHandler('posts')->handle($input);
$listFormFactory->addViolations($form, $result);                       // violations become form errors
```

The form is a view of the *input*: it never touches a query. It is a GET form named `''` with a `filters` child (one
field per active, non-locked filter whose type has a `FormFilterMapperInterface`) and a hidden `page`. Initial data is
the normalised default. What the query carries for a filter is passed on untouched (so an invalid value becomes a
violation instead of a silent `null`). A filter type without a mapper (e.g. `native`) has no field; register your own
mapper (autoconfigured, tag `sidus_filter.form_mapper`) for custom types. Per filter: `form: { label, type, options }`.

The sort field is a hidden input holding the API-style value plus `form.<sort>.vars.columns[key]` =
`{key, direction, primary, next}`: render a submit button (or link) per column key, `next` being the value to submit.

Themes (`twig.form_themes`): `@SidusFilter/form/base.html.twig` (unstyled), `@SidusFilter/form/bootstrap_6.html.twig`,
`@SidusFilter/form/bulma_1.html.twig`; a Tailwind skeleton to copy is in [docs/examples/tailwind.html.twig](examples/tailwind.html.twig).
Symfony ships no Bootstrap 6 layout yet: `bootstrap_6` extends the Bootstrap 5 layout. Translations (`en`, `fr`, domain
`messages`): `sidus.filter.date_range.{to,start_date,end_date}`, `sidus.filter.operator.<operator>`,
`sidus.filter.sort.{asc,desc}`.

## HTTP / API

```php
#[AsController]
final readonly class PostsController
{
    public function __construct(private QueryHandlerRegistry $registry, private ListResponseFactory $responses) {}

    public function __invoke(#[MapListInput('posts')] ListInput $input): JsonResponse
    {
        return $this->responses->create($this->registry->createQueryHandler('posts')->handle($input), $itemNormalizer);
    }
}
```

- `ListRequestParser`: `?filters[title]=x&sort=-date&page=2&per_page=50`, or the same structure as a JSON body (the body
  replaces the query's values). The `sort` value feeds the list's sort filter whatever its name. Keys are configurable
  under `sidus_filter.http`. Malformed JSON is a `400`.
- `ListResultNormalizer`: `{items, meta: {page, per_page, total, pages, next}}`; items go through an optional callable
  (`fn ($item) => $serializer->normalize($item, null, ['groups' => ['list']])`).
- `ListResponseFactory`: **strict by default**: violations answer `400 application/problem+json`
  (`{type, title, status, violations: [{path, message}]}`, never the submitted values). With `http.strict: false` (or
  `strict: false` per call) the response is `200` with the defaults applied and a `violations` key.

## Pagerfanta (`pagerfanta/core`)

`Sidus\FilterBundle\Pagerfanta\ListResultAdapter::createPager($result)` wraps a computed result (its page only; pages
beyond the end are allowed and empty). With `count: none` the number of results is estimated for prev/next navigation.
