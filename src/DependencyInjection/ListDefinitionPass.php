<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\DependencyInjection;

use Sidus\FilterBundle\Definition\DefinitionResolver;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Filter\FilterTypeInterface;
use Sidus\FilterBundle\Provider\Capabilities;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\CriterionCompilerInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds and validates every list definition once everything is registered (section 7.2): filter types, provider
 * factories and criterion compilers are read from their tagged services without instantiating them, through their
 * static methods, then the pure {@see DefinitionResolver} runs the structural and capability checks. The resolved definitions replace the raw
 * configuration as an argument of {@see ListDefinitionRegistry}.
 */
final class ListDefinitionPass implements CompilerPassInterface
{
    public const string FILTER_TYPE_TAG = 'sidus_filter.filter_type';
    public const string PROVIDER_FACTORY_TAG = 'sidus_filter.provider_factory';
    public const string CRITERION_COMPILER_TAG = 'sidus_filter.criterion_compiler';
    public const string RAW_LISTS_PARAMETER = 'sidus_filter.raw_lists';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ListDefinitionRegistry::class) || !$container->hasParameter(self::RAW_LISTS_PARAMETER)) {
            return;
        }
        $rawLists = $container->getParameterBag()->resolveValue($container->getParameter(self::RAW_LISTS_PARAMETER));
        $container->getParameterBag()->remove(self::RAW_LISTS_PARAMETER);

        $resolver = new DefinitionResolver(
            $this->filterTypes($container),
            $factories = $this->factories($container),
            $this->capabilities($container, array_keys($factories)),
        );

        $definitions = [];
        $countServices = [];
        foreach ($rawLists as $code => $config) {
            $list = $resolver->resolve((string) $code, $config);
            $this->checkCount($container, $list);
            if (!\in_array($list->count, [ListDefinition::COUNT_AUTO, ListDefinition::COUNT_NONE], true)) {
                $countServices[$list->count] = new Reference($list->count);
            }
            $definitions[$list->code] = $list->toArray();
        }

        $container->getDefinition(ListDefinitionRegistry::class)->setArgument('$definitions', $definitions);
        $container->getDefinition(QueryHandlerRegistry::class)->setArgument('$countStrategies', ServiceLocatorTagPass::register($container, $countServices));
    }

    /**
     * @return array<string, class-string<FilterTypeInterface>>
     */
    private function filterTypes(ContainerBuilder $container): array
    {
        $types = [];
        foreach (array_keys($container->findTaggedServiceIds(self::FILTER_TYPE_TAG)) as $id) {
            $class = $this->classOf($container, $id, FilterTypeInterface::class);
            $name = $class::getName();
            if (isset($types[$name])) {
                throw new ConfigurationException(\sprintf('Filter type "%s" is registered twice (%s and %s).', $name, $types[$name], $class));
            }
            $types[$name] = $class;
        }
        ksort($types);

        return $types;
    }

    /**
     * @return array<string, class-string<QueryHandlerFactoryInterface>>
     */
    private function factories(ContainerBuilder $container): array
    {
        $factories = [];
        foreach (array_keys($container->findTaggedServiceIds(self::PROVIDER_FACTORY_TAG)) as $id) {
            $class = $this->classOf($container, $id, QueryHandlerFactoryInterface::class);
            $provider = $class::getProvider();
            if (isset($factories[$provider])) {
                throw new ConfigurationException(\sprintf('Provider "%s" is registered twice (%s and %s).', $provider, $factories[$provider], $class));
            }
            $factories[$provider] = $class;
        }

        return $factories;
    }

    /**
     * @param list<string> $providers
     *
     * @return array<string, Capabilities>
     */
    private function capabilities(ContainerBuilder $container, array $providers): array
    {
        $compilers = array_fill_keys($providers, []);
        foreach (array_keys($container->findTaggedServiceIds(self::CRITERION_COMPILER_TAG)) as $id) {
            $class = $this->classOf($container, $id, CriterionCompilerInterface::class);
            if (isset($compilers[$class::getProvider()])) {
                $compilers[$class::getProvider()][] = $class;
            }
        }

        $capabilities = [];
        foreach ($compilers as $provider => $classes) {
            $capabilities[$provider] = Capabilities::fromCompilers($provider, $classes);
        }

        return $capabilities;
    }

    private function checkCount(ContainerBuilder $container, ListDefinition $list): void
    {
        if (\in_array($list->count, [ListDefinition::COUNT_AUTO, ListDefinition::COUNT_NONE], true)) {
            return;
        }
        $id = $list->count;
        if (!$container->has($id)) {
            throw new ConfigurationException(\sprintf('List "%s": count "%s" is neither "auto", "none" nor an existing service id.', $list->code, $id));
        }
        $class = $container->has($id) && $container->hasDefinition($id) ? $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass()) : null;
        if (\is_string($class) && class_exists($class) && !is_subclass_of($class, CountStrategyInterface::class)) {
            throw new ConfigurationException(\sprintf('List "%s": the count service "%s" (%s) must implement %s.', $list->code, $id, $class, CountStrategyInterface::class));
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $interface
     *
     * @return class-string<T>
     */
    private function classOf(ContainerBuilder $container, string $id, string $interface): string
    {
        $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);
        if (!\is_string($class) || !class_exists($class)) {
            throw new ConfigurationException(\sprintf('Cannot determine the class of the service "%s" tagged for sidus_filter.', $id));
        }
        if (!is_subclass_of($class, $interface)) {
            throw new ConfigurationException(\sprintf('The service "%s" (%s) must implement %s.', $id, $class, $interface));
        }

        return $class;
    }
}
