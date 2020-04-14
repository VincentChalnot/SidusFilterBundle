## Customization

This chapter covers two different things:
 - Adding a custom filter type for an existing provider, which is fairly simple.
 - Creating a custom provider, which is more complex but not needed often.

### Custom filter type

Note that you can use existing filters with custom form types without having to write custom filter types, simply
define the ````form_type```` option in the filter definition.

In this part we will cover how to create a custom filter type for the Doctrine Query Handler, the same principles
applies for any other provider but with variations that should be covered in the provider's documentation.

#### Summary

Create a service tagged with the ````sidus.filter_type```` tag and that implements the
````Sidus\FilterBundle\Filter\Type\FilterTypeInterface````.

For Doctrine filters, simply extends the ````AbstractDoctrineFilterType```` and implement the ````handleData```` method.

The next chapters cover these steps with more details.

#### Create the class

This example uses the code of the Doctrine/TextFilterType do demonstrate how it should be done.

````php
<?php

namespace AppBundle\Filter\Type\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\Type\Doctrine\AbstractSimpleFilterType;

class CustomFilterType extends AbstractSimpleFilterType
{
    /**
     * {@inheritdoc}
     */
    protected function applyDQL(QueryBuilder $qb, string $column, $data): string
    {
        $uid = uniqid('text', false); // Generate random parameter names to prevent collisions
        $qb->setParameter($uid, "%{$data}%"); // Add the parameter

        return "{$column} LIKE :{$uid}";
    }
}
````

This relies on the base logic defined in the ````AbstractSimpleFilterType````:

Note that it uses methods that are specific to the Doctrine provider: ````$queryHandler->getQueryBuilder()````
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


### Custom provider

This chapter covers the complex task of defining a new data provider with new filter types using this framework.

#### Define your custom QueryHandler

Define in your custom QueryHandler any method you need inside your filter types to provide the filtering mechanism to
your end result set.

This is a good practice to work with interfaces:

````php
<?php

namespace AppBundle\Query\Handler;

use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

interface CustomQueryHandlerInterface extends QueryHandlerInterface
{
    /**
     * This is just an example that doesn't represent anything
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return mixed
     */
    public function addCustomCondition(string $column, string $operator, $value);
}
````

Now the implementation:

````php
<?php

namespace AppBundle\Query\Handler;

use Pagerfanta\Pagerfanta;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Query\Handler\AbstractQueryHandler;

class CustomQueryHandler extends AbstractQueryHandler implements CustomQueryHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function addCustomCondition(string $column, string $operator, $value)
    {
        // Implement your custom code to either apply the condition immediately or store it for later
    }

    /**
     * {@inheritdoc}
     */
    protected function applySort(SortConfig $sortConfig)
    {
        $column = $sortConfig->getColumn();
        if ($column) {
            $direction = $sortConfig->getDirection() ? 'desc' : 'asc';
            // Apply the custom sort to your query mechanism
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createPager(): Pagerfanta
    {
        // Create your custom pager
    }
}
````

Now you need to create a factory that will be able to properly inject any custom services your QueryHandler needs.

#### Define your QueryHandlerFactory

Query handlers are created dynamically for each filter configuration so you need to tell the system how to properly
create your custom query handlers. This is done by defining a factory which is a fairly simple service:

````php
<?php

namespace AppBundle\Factory;

use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use AppBundle\CustomDependency;

class CustomQueryHandlerFactory implements QueryHandlerFactoryInterface
{
    /** @var FilterTypeRegistry */
    protected $filterTypeRegistry;

    /** @var CustomDependency */
    protected $myCustomDependency;

    /**
     * @param FilterTypeRegistry $filterTypeRegistry
     * @param CustomDependency   $myCustomDependency
     */
    public function __construct(
        FilterTypeRegistry $filterTypeRegistry,
        CustomDependency $myCustomDependency
    ) {
        $this->filterTypeRegistry = $filterTypeRegistry;
        $this->myCustomDependency = $myCustomDependency;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     *
     * @return QueryHandlerInterface
     */
    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface {
        return new CustomQueryHandler(
            $this->filterTypeRegistry,
            $queryHandlerConfiguration,
            $this->customDependency
        );
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'custom.code';
    }
}
````

Note that the factory must return your provider code. (You're free to choose anything unique)

Declare it as a service with the proper tag:

````yaml
services:
    AppBundle\Factory\CustomQueryHandlerFactory:
        public: false
        autowire: true
        tags:
            - { name: sidus.query_handler_factory }
````

You are now ready to create your custom filter types.

#### Declare your custom filter types

Declare an abstract class to simplify your developments:

````php
<?php

namespace AppBundle\Filter\Type;

use Sidus\FilterBundle\Filter\Type\AbstractFilterType;

abstract class AbstractCustomFilterType extends AbstractFilterType
{
    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'custom.code';
    }

    // Define any protected method common to your filter types here
}
````

Declare your actual filter type:

````php
<?php

namespace AppBundle\Filter\Type;

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

class CustomTextFilterType extends AbstractCustomFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        // First check that the QueryHandler you have is the one you need
        if (!$queryHandler instanceof CustomQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, CustomQueryHandlerInterface::class);
        }

        // Apply your custom logic using the publicly available methods of your CustomQueryHandler
        foreach ($filter->getAttributes() as $attribute) {
            $queryHandler->addCustomCondition($attribute, '=', $data);
        }
    }
}
````

Declare it as a service with the proper tags:

````yaml
services:
    AppBundle\Filter\Type\CustomTextFilterType:
        public: false
        arguments:
            - text
            - Symfony\Component\Form\Extension\Core\Type\TextType
        tags:
            - { name: sidus.filter_type }
````

#### Usage

You are ready to use your custom provider and all the filter types you defined:

````yaml
sidus_filter:
    configurations:
        mycustomentity:
            provider: custom.code
            sortable:
                - id
                - title
                # ...
            filters:
                title: ~ # Default type is text
                # ...
````

In this example we only defined the ````text```` filter type so only this one will be available
