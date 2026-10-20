<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\InMemory;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\ArraySource;
use Sidus\FilterBundle\InMemory\Comparison;
use Sidus\FilterBundle\InMemory\InMemoryCompiler;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\InMemory\ValueReader;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Provider\Capabilities;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

final class InMemoryProviderUnitTest extends TestCase
{
    public function testSortCompilesAComparatorWithNullsFirstAscending(): void
    {
        self::assertSame(-1, Comparison::sort(null, 0));
        self::assertSame(1, Comparison::sort(0, null));
        self::assertSame(0, Comparison::sort(null, null));
        self::assertSame(-1, Comparison::sort('B', 'a'), 'byte order: uppercase first');
        self::assertSame(-1, Comparison::sort(9, 10), 'numbers numerically');
        self::assertSame(-1, Comparison::sort('9', '10') * -1, 'two numeric strings compare as strings');
    }

    public function testComparisonSemantics(): void
    {
        self::assertTrue(Comparison::equals(5, '5'));
        self::assertTrue(Comparison::equals(1.0, 1));
        self::assertFalse(Comparison::equals('1.0', '1'));
        self::assertFalse(Comparison::equals(null, null));
        self::assertFalse(Comparison::equals('a', 'A'));
        self::assertTrue(Comparison::equals(new \DateTimeImmutable('2026-01-01T12:00:00+00:00'), new \DateTime('2026-01-01T13:00:00+01:00')));
        self::assertTrue(Comparison::equals(new \DateTimeImmutable('2026-01-01T12:00:00+00:00'), '2026-01-01T12:00:00Z'));
        self::assertNull(Comparison::compare('a', 1));
        self::assertNull(Comparison::compare(true, 1));
    }

    public function testReaderFansOutOverToManyAndPropagatesMissingRelations(): void
    {
        $reader = new ValueReader();
        $item = ['tags' => [['label' => 'a'], ['label' => 'b']], 'author' => null, 'plain' => ['x', 'y'], 'none' => []];

        self::assertSame(['a', 'b'], $reader->read($item, new Field('tags.label')));
        self::assertSame([null], $reader->read($item, new Field('author.name')));
        self::assertSame([['x', 'y']], $reader->read($item, new Field('plain')));
        self::assertSame(['x', 'y'], ValueReader::flatten($reader->read($item, new Field('plain'))));
        self::assertSame([], $reader->read(['tags' => []], new Field('tags.label')));
        self::assertSame([null], $reader->read($item, new Field('missing')));
    }

    public function testReaderUsesThePropertyAccessorOnObjects(): void
    {
        $post = new class() {
            public string $title = 'T';
            private string $secret = 's';

            public function getSlug(): string
            {
                return 'slug-'.$this->secret;
            }
        };
        $reader = new ValueReader();

        self::assertSame(['T'], $reader->read($post, new Field('title')));
        self::assertSame(['slug-s'], $reader->read($post, new Field('slug')));
        self::assertSame([null], $reader->read($post, new Field('secret')));
    }

    public function testReaderJsonPathsAndCasts(): void
    {
        $reader = new ValueReader();
        $item = ['totals' => ['regression' => '42', 'deep' => ['a' => 1]], 'raw' => '{"k": {"n": 7}}'];

        self::assertSame([42], $reader->read($item, Field::parse('totals->regression::int')));
        self::assertSame([1], $reader->read($item, Field::parse('totals->deep->a')));
        self::assertSame([null], $reader->read($item, Field::parse('totals->nope')));
        self::assertSame([7], $reader->read($item, Field::parse('raw->k->n')));
        self::assertSame([42.0], $reader->read($item, Field::parse('totals->regression::float')));
        self::assertSame([null], $reader->read(['totals' => ['x' => 'abc']], Field::parse('totals->x::int')));
        self::assertSame([true], $reader->read(['f' => 'true'], Field::parse('f::bool')));
    }

    public function testCompilerPredicates(): void
    {
        $compiler = new InMemoryCompiler(InMemoryQueryHandlerFactory::defaultCompilers());
        $item = ['name' => 'Zoë', 'n' => 5, 'tags' => []];

        self::assertTrue($compiler->compile(new Contains('name', 'ZO'))($item));
        self::assertFalse($compiler->compile(new Contains('name', 'ZO', true))($item));
        self::assertTrue($compiler->compile(new IsNull('missing'))($item));
        self::assertTrue($compiler->compile(new IsEmpty('tags'))($item));
        self::assertTrue($compiler->compile(new Range('n', 5, 5))($item));
        self::assertFalse($compiler->compile(new Range('n', 5, 5, false))($item));
        self::assertFalse($compiler->compile(new Range('name', 1))($item), 'incomparable never matches');
        self::assertTrue($compiler->compile(new Native('in_memory', static fn (array $i): bool => 5 === $i['n']))($item));
    }

    public function testNativeForAnotherProviderIsRefused(): void
    {
        $compiler = new InMemoryCompiler(InMemoryQueryHandlerFactory::defaultCompilers());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be applied by the in_memory provider');

        $compiler->compile(new Native('doctrine', 'x'));
    }

    public function testMissingCompilerIsReported(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('has no compiler for criterion');

        new InMemoryCompiler([])->compile(new Equals('a', 1));
    }

    public function testShippedCompilersCoverTheWholeVocabulary(): void
    {
        $capabilities = Capabilities::fromCompilers('in_memory', array_map(static fn ($c): string => $c::class, InMemoryQueryHandlerFactory::defaultCompilers()));

        self::assertCount(14, $capabilities->supportedClasses());
        self::assertNull($capabilities->supports(Equals::class));
    }

    public function testUnknownSourceIsReported(): void
    {
        $factory = InMemoryQueryHandlerFactory::standalone([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no in-memory source "posts"');

        $factory->createQueryHandler(new ListDefinition('l', 'in_memory', ['source' => 'posts', 'identifier' => 'id']), FilterTypeRegistry::builtIn(), null);
    }

    public function testObjectsAndWorkerStyleReuseOfTheFactory(): void
    {
        $factory = InMemoryQueryHandlerFactory::standalone(['posts' => new ArraySource([['id' => 2, 'n' => 'b'], ['id' => 1, 'n' => 'a']])]);
        $definition = new ListDefinition('l', 'in_memory', ['source' => 'posts', 'identifier' => 'id'], filters: [
            new FilterDefinition('n', 'text', options: ['operators' => ['contains'], 'case_sensitive' => false]),
            new FilterDefinition('sort', 'sort', options: ['columns' => ['id' => ['field' => ['path' => 'id', 'json' => [], 'cast' => null], 'provider_options' => []]], 'default' => [['column' => 'id', 'direction' => 'asc']], 'max_columns' => 3]),
        ]);
        $types = FilterTypeRegistry::builtIn();

        $a = $factory->createQueryHandler($definition, $types, null);
        $b = $factory->createQueryHandler($definition, $types, null);

        self::assertNotSame($a, $b);
        self::assertSame([['id' => 2, 'n' => 'b']], $a->handle(ListInput::fromArray(['filters' => ['n' => 'b']]))->items());
        self::assertSame([['id' => 1, 'n' => 'a'], ['id' => 2, 'n' => 'b']], $b->handle(new ListInput())->items(), 'no leaked filter');
    }
}
