## Installation

### Bundle setup

Require this bundle with composer:

````bash
$ composer require sidus/filter-bundle
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

### Setup basic configuration

This step is strongly recommended although optional.

If you want to use the basic form templates, just add the filter's form template:

````yaml
twig:
    # ...
    form_themes:
        - '@SidusFilter/Form/fields.html.twig'
````

If you want to use Bootstrap:

````yaml
# Twig Configuration
twig:
    # ...
    form_themes:
        - 'bootstrap_4_layout.html.twig'
        - '@SidusFilter/Form/bootstrap.fields.html.twig'
````
