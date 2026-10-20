<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Criterion;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Criterion\AllOf;
use Sidus\FilterBundle\Criterion\AnyOf;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\In;
use Sidus\FilterBundle\Criterion\OrderBy;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Criterion\SortDirection;
use Sidus\FilterBundle\Definition\Field;

final class CriterionTest extends TestCase
{
    public function testFieldIsNormalisedFromString(): void
    {
        $criterion = new Equals('author.name', 'x');

        self::assertEquals(new Field('author.name'), $criterion->field);
    }

    public function testInRejectsEmptyList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new In('status', []);
    }

    public function testRangeRequiresABound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Range('views');
    }

    public function testCompositesRequireCriteria(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnyOf();
    }

    public function testAllOfAndSortKeepOrder(): void
    {
        $all = new AllOf(new Equals('a', 1), new Equals('b', 2));
        $sort = new Sort(new OrderBy('a'), new OrderBy('b', SortDirection::Desc));

        self::assertSame('b', $all->criteria[1]->field->path);
        self::assertSame(SortDirection::Desc, $sort->columns[1]->direction);
        self::assertSame(SortDirection::Asc, $sort->columns[0]->direction);
    }

    public function testSortRequiresAColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Sort();
    }
}
