Sidus/FilterBundle
=====================

This bundle allows you to create filters using Symfony's Form component to alter result sets of any kind:
- Doctrine QueryBuilder (Natively in this bundle)
- [ElasticSearch query](https://github.com/VincentChalnot/SidusElasticaFilterBundle)
- [EAVQueryBuilder from the Sidus/EAVModelBundle](https://vincentchalnot.github.io/SidusEAVModelBundle/)
- [Akeneo's Query API](https://github.com/cleverage/eav-manager-akeneo-product-bundle)
- Any data provider implementing filters and a pagination process.

See the [Sidus/DataGridBundle](https://github.com/VincentChalnot/SidusDataGridBundle) for a fully featured datagrid
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
            new Sidus\FilterBundle\SidusFilterBundle(),
            // ...
        ];
    }
}
````

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



## Specific features

The ````choice```` filter type will automatically load the choices from your attribute configuration.
