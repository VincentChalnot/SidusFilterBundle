Sidus/FilterBundle
=====================

The configuration example of this documentation can be seen on the [live demo website](http://filter-demo.sidus.fr). 
Source of the demo is available [here](https://github.com/VincentChalnot/SidusFilterDemo).

This bundle allows you to create filters using Symfony's Form component to alter result sets of any kind:
 - Doctrine QueryBuilder (Natively in this bundle)
 - [ElasticSearch query](https://github.com/VincentChalnot/SidusElasticaFilterBundle)
 - [EAVQueryBuilder from the Sidus/EAVModelBundle](https://vincentchalnot.github.io/SidusEAVModelBundle/)
 - [Akeneo's Query API](https://github.com/cleverage/eav-manager-akeneo-product-bundle)
 - Any data provider implementing filters and a pagination process.

This bundle does not include datagrid management, see the
[Sidus/DataGridBundle](https://github.com/VincentChalnot/SidusDataGridBundle) for a fully featured datagrid
component based on this bundle.

## Table of content

 - [01 - Installation](Documentation/01-install.md)
 - [02 - Configuration](Documentation/02-configuration.md)
 - [03 - Usage](Documentation/03-usage.md)
 - [04 - Internals](Documentation/04-internals.md)
 - [05 - Customization](Documentation/05-customization.md)
