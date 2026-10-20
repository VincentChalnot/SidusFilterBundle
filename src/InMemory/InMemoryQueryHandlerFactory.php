<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\InMemory;

use Psr\Container\ContainerInterface;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\Compiler\AllOfCompiler;
use Sidus\FilterBundle\InMemory\Compiler\AnyOfCompiler;
use Sidus\FilterBundle\InMemory\Compiler\ContainsCompiler;
use Sidus\FilterBundle\InMemory\Compiler\EndsWithCompiler;
use Sidus\FilterBundle\InMemory\Compiler\EqualsCompiler;
use Sidus\FilterBundle\InMemory\Compiler\InCompiler;
use Sidus\FilterBundle\InMemory\Compiler\IsEmptyCompiler;
use Sidus\FilterBundle\InMemory\Compiler\IsNullCompiler;
use Sidus\FilterBundle\InMemory\Compiler\MatchesCompiler;
use Sidus\FilterBundle\InMemory\Compiler\NativeCompiler;
use Sidus\FilterBundle\InMemory\Compiler\NotCompiler;
use Sidus\FilterBundle\InMemory\Compiler\RangeCompiler;
use Sidus\FilterBundle\InMemory\Compiler\SortCompiler;
use Sidus\FilterBundle\InMemory\Compiler\StartsWithCompiler;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reference provider: the executable meaning of every criterion over arrays and objects. List options:
 * `source` (the id of an {@see InMemorySourceInterface}), `identifier` (field used as sort tie-breaker, default `id`).
 */
final class InMemoryQueryHandlerFactory implements QueryHandlerFactoryInterface
{
    public const string PROVIDER = 'in_memory';

    private readonly InMemoryCompiler $compiler;

    /**
     * @param iterable<InMemoryCriterionCompilerInterface> $compilers
     */
    public function __construct(private readonly ContainerInterface $sources, iterable $compilers)
    {
        $this->compiler = new InMemoryCompiler($compilers);
    }

    /**
     * Without a container: every shipped compiler and the given sources, by id.
     *
     * @param array<string, InMemorySourceInterface> $sources
     */
    public static function standalone(array $sources): self
    {
        return new self(
            new ServiceLocator(array_map(static fn (InMemorySourceInterface $source): \Closure => static fn (): InMemorySourceInterface => $source, $sources)),
            self::defaultCompilers(),
        );
    }

    /**
     * @return list<InMemoryCriterionCompilerInterface>
     */
    public static function defaultCompilers(): array
    {
        return [
            new EqualsCompiler(), new InCompiler(), new ContainsCompiler(), new StartsWithCompiler(), new EndsWithCompiler(),
            new RangeCompiler(), new IsNullCompiler(), new IsEmptyCompiler(), new MatchesCompiler(), new NotCompiler(),
            new AnyOfCompiler(), new AllOfCompiler(), new NativeCompiler(), new SortCompiler(),
        ];
    }

    public static function getProvider(): string
    {
        return self::PROVIDER;
    }

    public static function configureListOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('source');
        $resolver->setAllowedTypes('source', 'string');
        $resolver->setDefault('identifier', 'id');
        $resolver->setAllowedTypes('identifier', 'string');
    }

    public static function configureFilterOptions(OptionsResolver $resolver): void
    {
    }

    public function createQueryHandler(ListDefinition $definition, FilterTypeRegistry $filterTypes, ?CountStrategyInterface $countStrategy): QueryHandlerInterface
    {
        $id = $definition->options['source'];
        if (!$this->sources->has($id)) {
            throw new \LogicException(\sprintf('List "%s": no in-memory source "%s" (tag the service with sidus_filter.in_memory_source).', $definition->code, $id));
        }

        return new InMemoryQueryHandler($definition, $filterTypes, $countStrategy, $this->sources->get($id), $this->compiler);
    }
}
