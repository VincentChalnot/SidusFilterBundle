<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use Sidus\FilterBundle\Criterion\OrderBy;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Criterion\SortDirection;
use Sidus\FilterBundle\Definition\Field;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateRangeAndSortTest extends FilterTypeTestCase
{
    public function testDateOnlyBoundsCoverWholeDaysInTheConfiguredTimezone(): void
    {
        $filter = $this->definition('date_range', ['timezone' => 'Europe/Paris']);

        $criterion = $this->apply($filter, ['from' => '2026-10-20', 'to' => '2026-10-21']);

        self::assertInstanceOf(Range::class, $criterion);
        self::assertSame('2026-10-20T00:00:00.000000+02:00', $criterion->min->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2026-10-21T23:59:59.999999+02:00', $criterion->max->format('Y-m-d\TH:i:s.uP'));
        self::assertTrue($criterion->minInclusive && $criterion->maxInclusive);
    }

    public function testOpenBoundsAndDateTimeValues(): void
    {
        $filter = $this->definition('date_range');
        $to = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');

        $criterion = $this->apply($filter, ['from' => '', 'to' => $to]);

        self::assertInstanceOf(Range::class, $criterion);
        self::assertNull($criterion->min);
        self::assertEquals($to, $criterion->max);
        self::assertSame('2026-01-01T10:30:00+01:00', $this->apply($filter, ['to' => '2026-01-01T10:30:00+01:00'])->max->format('c'));
        self::assertNull($this->apply($filter, ['from' => '', 'to' => '']));
    }

    #[DataProvider('invalidRanges')]
    public function testDateViolations(mixed $raw, array $paths): void
    {
        self::assertSame($paths, $this->apply($this->definition('date_range'), $raw));
    }

    public static function invalidRanges(): iterable
    {
        yield 'not a map' => ['2026-01-01', ['']];
        yield 'garbage' => [['from' => 'yesterday'], ['from']];
        yield 'rolled over day' => [['to' => '2026-02-30'], ['to']];
        yield 'both bad' => [['from' => 'x', 'to' => 'y'], ['from', 'to']];
        yield 'inverted' => [['from' => '2026-02-01', 'to' => '2026-01-01'], ['']];
    }

    private function sort(array $extra = []): \Sidus\FilterBundle\Definition\FilterDefinition
    {
        return $this->definition('sort', $extra + [
            'columns' => ['title' => 'title', 'date' => 'publishedAt', 'author' => ['field' => 'author.name', 'provider_options' => ['doctrine' => ['x' => 1]]]],
            'default' => [['column' => 'date', 'direction' => 'desc']],
        ]);
    }

    public function testApiStyleString(): void
    {
        self::assertEquals(
            new Sort(new OrderBy('publishedAt', SortDirection::Desc), new OrderBy('title')),
            $this->apply($this->sort(), '-date, title'),
        );
        self::assertEquals(new Sort(new OrderBy('title')), $this->apply($this->sort(), '+title'));
    }

    public function testStructuredForms(): void
    {
        $filter = $this->sort();

        self::assertEquals(new Sort(new OrderBy('title', SortDirection::Desc)), $this->apply($filter, ['column' => 'title', 'direction' => 'DESC']));
        self::assertEquals(new Sort(new OrderBy('title', SortDirection::Desc)), $this->apply($filter, ['title' => 'desc']));
        self::assertEquals(
            new Sort(new OrderBy('title'), new OrderBy('publishedAt', SortDirection::Desc)),
            $this->apply($filter, ['title', ['column' => 'date', 'direction' => 'desc']]),
        );
    }

    public function testColumnProviderOptionsTravelWithTheOrderBy(): void
    {
        $criterion = $this->apply($this->sort(), 'author');

        self::assertEquals(new Sort(new OrderBy(new Field('author.name'), providerOptions: ['doctrine' => ['x' => 1]])), $criterion);
    }

    public function testBlankFallsBackToTheDefault(): void
    {
        self::assertEquals(new Sort(new OrderBy('publishedAt', SortDirection::Desc)), $this->apply($this->sort(), null));
        self::assertNull($this->apply($this->sort(['default' => []]), null));
    }

    #[DataProvider('invalidSorts')]
    public function testSortViolations(mixed $raw, array $paths): void
    {
        self::assertSame($paths, $this->apply($this->sort(), $raw));
    }

    public static function invalidSorts(): iterable
    {
        yield 'property path is not a key' => ['author.password', ['0']];
        yield 'unknown key' => ['-nope', ['0']];
        yield 'bad direction' => [['column' => 'title', 'direction' => 'sideways'], ['0']];
        yield 'duplicate' => ['title,-title', ['1']];
        yield 'too many' => ['title,date,author,title', ['3', '']];
        yield 'garbage' => [42, ['']];
        yield 'only commas' => [',,', ['']];
    }

    public function testConfigurationErrors(): void
    {
        foreach ([
            [[]],
            [['columns' => []]],
            [['columns' => ['a b' => 'x']]],
            [['columns' => ['a' => 'x'], 'default' => [['column' => 'b']]]],
            [['columns' => ['a' => 'x'], 'default' => [['column' => 'a', 'direction' => 'up']]]],
            [['columns' => ['a' => 'x'], 'max_columns' => 0]],
        ] as [$options]) {
            try {
                $this->definition('sort', $options);
                self::fail('Expected a configuration error for '.json_encode($options));
            } catch (\Throwable $e) {
                self::assertTrue(true);
            }
        }
    }
}
