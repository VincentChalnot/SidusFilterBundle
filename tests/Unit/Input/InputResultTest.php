<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Input;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Input\Violation;
use Sidus\FilterBundle\Input\ViolationList;
use Sidus\FilterBundle\Result\ListResult;

final class InputResultTest extends TestCase
{
    public function testFromArrayReadsFiltersPagingAndTopLevelSort(): void
    {
        $input = ListInput::fromArray(['filters' => ['title' => 'x'], 'sort' => '-date', 'page' => '2', 'per_page' => '5']);

        self::assertSame(['title' => 'x', 'sort' => '-date'], $input->filters);
        self::assertSame('2', $input->page);
        self::assertSame('5', $input->perPage);
    }

    public function testExplicitSortFilterWinsOverTopLevelSort(): void
    {
        $input = ListInput::fromArray(['filters' => ['sort' => 'a'], 'sort' => 'b', 'unknown' => 1]);

        self::assertSame(['sort' => 'a'], $input->filters);
    }

    public function testNonArrayFiltersAreIgnored(): void
    {
        self::assertSame([], ListInput::fromArray(['filters' => 'x'])->filters);
    }

    public function testWithTotalCountIsImmutable(): void
    {
        $input = new ListInput(['a' => 1]);
        $with = $input->withTotalCount(3);

        self::assertNull($input->totalCount);
        self::assertSame(3, $with->totalCount);
        self::assertInstanceOf(\Closure::class, $input->withTotalCount(static fn () => 1)->totalCount);
        $this->expectException(\InvalidArgumentException::class);
        $input->withTotalCount(-1);
    }

    public function testViolationPrefixAndList(): void
    {
        $violation = new Violation('value', 'bad', 'x');
        $list = new ViolationList()->with($violation->withPathPrefix('filters.title'), new Violation('', 'm')->withPathPrefix('page'));

        self::assertCount(2, $list);
        self::assertFalse($list->isEmpty());
        self::assertSame('filters.title.value', $list->toArray()[0]['path']);
        self::assertSame('page', $list->toArray()[1]['path']);
    }

    public function testTotalIsLazyAndComputedOnce(): void
    {
        $calls = 0;
        $result = new ListResult([1, 2], 1, 2, static function () use (&$calls): int {
            ++$calls;

            return 5;
        }, new ViolationList());

        self::assertSame(0, $calls);
        self::assertSame(5, $result->total());
        self::assertSame(5, $result->total());
        self::assertSame(1, $calls);
        self::assertSame(3, $result->pages());
        self::assertTrue($result->hasNextPage());
    }

    public function testUnknownTotalAndNextPageFlag(): void
    {
        $result = new ListResult([1], 1, 1, null, new ViolationList(), hasNextPage: true);

        self::assertNull($result->total());
        self::assertNull($result->pages());
        self::assertTrue($result->hasNextPage());
    }
}
