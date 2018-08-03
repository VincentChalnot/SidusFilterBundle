Sidus/FilterBundle
=====================

The configuration example of this documentation can be seen on the [live demo website](http://filter-demo.sidus.fr). 
Source of the demo is available [here](https://github.com/VincentChalnot/SidusEAVFilterDemo).

This bundle allows you to create filters using Symfony's Form component to alter result sets of any kind:
- Doctrine QueryBuilder (Natively in this bundle)
- [ElasticSearch query](https://github.com/VincentChalnot/SidusElasticaFilterBundle)
- [EAVQueryBuilder from the Sidus/EAVModelBundle](https://vincentchalnot.github.io/SidusEAVModelBundle/)
- [Akeneo's Query API](https://github.com/cleverage/eav-manager-akeneo-product-bundle)
- Any data provider implementing filters and a pagination process.

This bundle does not include datagrid management, see the
[Sidus/DataGridBundle](https://github.com/VincentChalnot/SidusDataGridBundle) for a fully featured datagrid
component based on this bundle.

## Installation

### Bundle setup

Require this bundle with composer:

````bash
$ composer require sidus/filter-bundle "1.4.*"
````

### Add the bundle to AppKernel.php

````php
<?php
/**
 * app/AppKernel.php
 */
class AppKernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            // Optional, only needed if you want to use the pagerfanta() twig function
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            
            // Optional, see the benefits of this bundle here: https://github.com/VincentChalnot/SidusBaseBundle
            new Sidus\BaseBundle\SidusBaseBundle(),
            
            // Required, obviously
            new Sidus\FilterBundle\SidusFilterBundle(),

            // ...
        ];
    }
}
````

### Setup basic configuration

This step is strongly 

## Configuration

For this example, we will be using the ````doctrine```` data provider but the same principles applies for any data
provider.

````yaml
sidus_filter:
    configurations:
        news:
            # Tells the system to use the Doctrine filter engine
            provider: doctrine

            # This is a variable node, you can put anything you want but some provider
            # will require specific options to work
            options:
                # Required by the doctrine provider to select the proper data source
                entity: AppBundle\Entity\News

            # Optional, default to 15
            results_per_page: 10

            # Optional, list any field you want to enable sort on
            sortable:
                - id
                - title
                - publicationDate
                - publicationStatus
                - updatedAt

            # Optional, define the default sort
            default_sort:
                publicationDate: DESC

            # Optional, define all the filters you need
            filters:
                title: ~ # Default type is text, you can leave the configuration blank

                author: # This code won't be used when you declare the "attributes" property
                    # Override the default form widget but keep the filter type logic
                    form_type: AppBundle\Form\Type\AuthorType

                    # Search in multiple fields at the same time (using OR)
                    attributes: [author.fullName, author.email]

                # Date range example
                publicationDate:
                    type: date_range

                # Choice filter can be used for scalar values
                publicationStatus:
                    type: choice

                    # You can define a default value
                    default: published

                    # Use form_options to provide any additional Symfony form options to the
                    # form widget
                    form_options:
                        # Choices are optional, the filter can create a list automatically
                        choices:
                            Published: published
                            Draft: draft
                            # ....

                # Here we filter a relationship to the Category entity
                categories:
                    type: choice
                    form_options:
                        # Allows multiple choices (Standard ChoiceType option)
                        multiple: true

                # Default filters can be hidden from the form to force filtering
                deleted:
                    default: 0

                    # This is a variable node, you can use it for you own custom needs
                    options:
                        # Hide completely this filter in the form,
                        # this is the only default option defined by this bundle
                        hidden: true
````

### Configuration quick reference

````yaml
sidus_filter:
    configurations:
        <configuration_code>:
            provider: <provider_code> # Required
            options:
                entity: <class_name> # Required by doctrine provider
                # Any custom option
            results_per_page: <int> # default 15
            sortable:
                - <property_code>
                # ...
            default_sort:
                <property_code>: DESC|ASC
            filters:
                <property_code>: # Can also be any code if using "attributes" property
                    type: <filter_type> # text|date_range|choice|...
                    label: <string> # Translation id or label
                    default: <mixed> # Default filter value
                    options: {} # Variable node
                    form_type: <form_type> # Custom form widget
                    form_options: {} # Standard Symfony form options
                    attributes: # 
                        - <property_code>
                        # ...
````

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
[https://github.com/VincentChalnot/SidusEAVFilterDemo/blob/master/src/AppBundle/Action/SearchAction.php](https://github.com/VincentChalnot/SidusEAVFilterDemo/blob/master/src/AppBundle/Action/SearchAction.php)

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
[https://github.com/VincentChalnot/SidusEAVFilterDemo/blob/master/app/Resources/views/Search/action.html.twig](https://github.com/VincentChalnot/SidusEAVFilterDemo/blob/master/app/Resources/views/Search/action.html.twig)

## How does it work?

The majority of the work is split between the QueryHandler and the FilterTypes, which are both specific to each
provider. Instantiating the proper QueryHandler and Filter Types is the roles of the QueryHandlerFactory and the
FilterTypeRegistry.

The QueryBuilder builds the form from the configuration and manage the request and the pager. It also ensures each
filter types gets access to all the data it needs to alter the query.

Filter types have access to the QueryHandler for any custom need (access to the QueryBuilder for Doctrine for example)
and to the form data. It's their role to build the query properly from the data they receive.

By convention all filter types don't do anything if the data they receive is ````null````.

## Customization

### Custom filter type

Note that you can use existing filters with custom form types without having to write custom filter types, simply
define the ````form_type```` option in the filter definition.

In this part we will cover how to create a custom filter type for the Doctrine Query Handler, the same principles
applies for any other provider but with variations that should be covered in the provider's documentation.

#### Create the class

This example uses the code of the Doctrine/TextFilterType do demonstrate how it should be done.

````php
<?php

namespace AppBundle\Filter\Type\Doctrine;

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\Doctrine\DoctrineQueryHandlerInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\FilterBundle\Filter\Type\Doctrine\AbstractDoctrineFilterType;

class CustomFilterType extends AbstractDoctrineFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        // Check that the query handler is of the proper type
        if (!$queryHandler instanceof DoctrineQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, DoctrineQueryHandlerInterface::class);
        }

        // Fetch the query builder
        $qb = $queryHandler->getQueryBuilder();
        $dql = []; // Prepare an array of DQL statements

        // Fetch all attributes references (all filters must support multiple attributes)
        foreach ($this->getFullAttributeReferences($filter, $queryHandler) as $column) {
            $uid = uniqid('text'); // Generate random parameter names to prevent collisions
            
            // This example uses the same logic as the TextFilterType
            $dql[] = "{$column} LIKE :{$uid}"; // Add the dql statement to the list
            $qb->setParameter($uid, '%'.$data.'%'); // Add the parameter
        }

        // If the array of DQL statements is not empty (it shouldn't), apply it on the query builder with a OR
        if (0 < \count($dql)) {
            $qb->andWhere(implode(' OR ', $dql)); // implode all your DQL statements
        }
    }
}
````

Note that here we use methods that are specific to the Doctrine provider: ````$queryHandler->getQueryBuilder()````
is a custom method implemented only by the ````DoctrineQueryHandlerInterface````.

The ````AbstractDoctrineFilterType::getFullAttributeReferences()```` method is a shortcut to fetch a resolved list of
DQL attributes with the proper joins applied to the query builder for nested attributes so you don't have to worry about
it when you apply your conditions.

#### Declare it as a service

````yaml
services:
    AppBundle\Filter\Type\Doctrine\CustomFilterType:
        public: false
        arguments:
            - custom_type_code
            - <FormType>
        tags:
            - { name: sidus.filter_type }
````

From here you can use it in your configuration by setting the ````type```` filter option to your custom code.
