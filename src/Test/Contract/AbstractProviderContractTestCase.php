<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Test\Contract;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * The shared contract every provider must honour: one fixture ({@see FixtureDataset}), cases as data
 * ({@see ContractCases}), the reference meaning being the in-memory provider (design section 4).
 *
 * A provider test extends this class, loads the fixture in its own storage (setUp), and implements
 * {@see providerName()}, {@see listOptions()} and {@see createHandler()}. Strict mode: a supported criterion must
 * behave like the reference. A platform limitation found by the suite is declared in {@see knownDivergences()};
 * the suite then asserts that the case *does* diverge, so the list cannot rot. Each entry is either fixed or
 * turned into a capability refusal before release.
 */
abstract class AbstractProviderContractTestCase extends TestCase
{
    /** The provider name used in the list definitions. */
    abstract protected function providerName(): string;

    /**
     * The provider-specific list options (entity, source, identifier...) of the fixture list. The identifier of
     * the fixture is the `id` field.
     *
     * @return array<string, mixed>
     */
    abstract protected function listOptions(): array;

    abstract protected function createHandler(ListDefinition $definition): QueryHandlerInterface;

    /**
     * @return array<string, string> case id => why this provider cannot match the reference
     */
    protected function knownDivergences(): array
    {
        return [];
    }

    /** The fixture id of a result item (array or object with an `id`). */
    protected function itemId(mixed $item): int
    {
        return \is_array($item) ? $item['id'] : PropertyAccess::createPropertyAccessor()->getValue($item, 'id');
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        foreach (ContractCases::all() as $id => $case) {
            yield $id => [$id, $case];
        }
    }

    /**
     * @param array{input: array<string, mixed>, ids: list<int>, total: int, violations: list<string>, specs: array<string, array<string, mixed>|null>, pagination: array<string, int>} $case
     */
    #[DataProvider('cases')]
    public function testCase(string $id, array $case): void
    {
        $result = $this->createHandler($this->definition($case['specs'], $case['pagination']))->handle(ListInput::fromArray($case['input']));
        $expected = ['ids' => $case['ids'], 'total' => $case['total'], 'violations' => $case['violations']];
        $actual = [
            'ids' => array_map($this->itemId(...), $result->items()),
            'total' => $result->total(),
            'violations' => array_map(static fn (array $violation): string => $violation['path'], $result->violations()->toArray()),
        ];

        if (isset($this->knownDivergences()[$id])) {
            self::assertNotSame($expected, $actual, \sprintf('Case "%s" is declared as a known divergence (%s) but now matches the reference: remove the entry.', $id, $this->knownDivergences()[$id]));

            return;
        }
        self::assertSame($expected, $actual, \sprintf('Case "%s" diverges from the reference.', $id));
    }

    public function testKnownDivergencesReferToExistingCases(): void
    {
        self::assertSame([], array_diff_key($this->knownDivergences(), ContractCases::all()), 'Known divergences must name existing case ids.');
    }

    public function testAHandlerIsSingleUse(): void
    {
        $handler = $this->createHandler($this->definition());
        $handler->handle(new ListInput());

        $this->expectException(\LogicException::class);

        $handler->handle(new ListInput());
    }

    public function testASuppliedTotalReplacesTheCountAndIsLazy(): void
    {
        $calls = 0;
        $result = $this->createHandler($this->definition())->handle(
            new ListInput(perPage: 2)->withTotalCount(static function () use (&$calls): int {
                ++$calls;

                return 42;
            }),
        );

        self::assertCount(2, $result->items());
        self::assertSame(0, $calls);
        self::assertSame(42, $result->total());
        self::assertSame(42, $result->total());
        self::assertSame(1, $calls);
    }

    /**
     * @param array<string, array<string, mixed>|null> $overrides filter key => replacement spec (null removes it)
     * @param array<string, int>                       $pagination
     */
    protected function definition(array $overrides = [], array $pagination = []): ListDefinition
    {
        $specs = array_filter([...FixtureDataset::filterSpecs(), ...$overrides], static fn (?array $spec): bool => null !== $spec);
        $types = FilterTypeRegistry::builtIn();
        $filters = [];
        foreach ($specs as $key => $spec) {
            $resolver = new OptionsResolver();
            $types->get($spec['type'])->configureOptions($resolver);
            $filters[] = new FilterDefinition(
                key: (string) $key,
                type: $spec['type'],
                attributes: $spec['attributes'] ?? [],
                options: $resolver->resolve($spec['options'] ?? []),
                locked: $spec['locked'] ?? false,
                default: $spec['default'] ?? null,
            );
        }

        return new ListDefinition(
            'contract',
            $this->providerName(),
            $this->listOptions(),
            $pagination['per_page'] ?? 10,
            $pagination['max_per_page'] ?? 50,
            ListDefinition::COUNT_AUTO,
            $filters,
        );
    }
}
