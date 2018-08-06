## Internals

### How does it work?

The majority of the work is split between the QueryHandler and the FilterTypes, which are both specific to each
provider. Instantiating the proper QueryHandler and Filter Types is the roles of the QueryHandlerFactory and the
FilterTypeRegistry.

The QueryBuilder builds the form from the configuration and manage the request and the pager. It also ensures each
filter types gets access to all the data it needs to alter the query.

Filter types have access to the QueryHandler for any custom need (access to the QueryBuilder for Doctrine for example)
and to the form data. It's their role to build the query properly from the data they receive.

All filter types don't do anything if the data they receive is ````null````.
