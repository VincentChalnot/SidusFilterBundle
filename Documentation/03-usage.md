## Usage

This chapter is only relevant if you use this bundle in standalone. If you simply want to render datagrids, use the
[Sidus/DataGridBundle](https://github.com/VincentChalnot/SidusDataGridBundle).

### Controller/Action side

````php
<?php
/**
 * @var \Symfony\Component\Form\FormFactoryInterface $formFactory 
 * @var \Sidus\FilterBundle\Registry\QueryHandlerRegistry $queryHandlerRegistry
 */

// Create a form builder, configure it any way you want
$builder = $formFactory->createBuilder();

// Fetch you query handler using the registry
$queryHandler = $queryHandlerRegistry->getQueryHandler('<configuration_code>');

// Build the final form using your builder
$form = $queryHandler->buildForm($builder);

// Handle the request to apply filters from the form submission
$queryHandler->handleRequest($request);
// Alternatively, you can use $queryHandler->handleArray() to hydrate the form data from an array manually

// Bind these variables to your view
$viewParameters = [
    'form' => $form->createView(),
    'results' => $queryHandler->getPager(),
];
````

In real life, this is how it goes:
[https://github.com/VincentChalnot/SidusFilterDemo/blob/master/src/AppBundle/Action/SearchAction.php](https://github.com/VincentChalnot/SidusFilterDemo/blob/master/src/AppBundle/Action/SearchAction.php)

### Rendering side

````twig
{# Render the filters form #}
{{ form(form) }}

{# Iterate over each result to render it any way you want #}
{% for result in results %}
    {# Do stuff here #}
{% endfor %}

{# Render the pagination #}
{{ pagerfanta(results) }}
````

Note that if you declare sortable fields, all the sort buttons will be displayed after the filters, if you want to
customize the rendering of the form, checkout this more complex rendering example:
[https://github.com/VincentChalnot/SidusFilterDemo/blob/master/app/Resources/views/Search/action.html.twig](https://github.com/VincentChalnot/SidusFilterDemo/blob/master/app/Resources/views/Search/action.html.twig)

This example is feature in [the live demo](http://filter-demo.sidus.fr/).
