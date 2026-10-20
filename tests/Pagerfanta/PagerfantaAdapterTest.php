<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Pagerfanta;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\ArraySource;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Pagerfanta\ListResultAdapter;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Result\ListResult;

final class PagerfantaAdapterTest extends TestCase
{
    private function listResult(string $count, int $page, int $perPage = 2): ListResult
    {
        $items = array_map(static fn (int $id): array => ['id' => $id], range(1, 5));

        return InMemoryQueryHandlerFactory::standalone(['s' => new ArraySource($items)])
            ->createQueryHandler(new ListDefinition('l', 'in_memory', ['source' => 's', 'identifier' => 'id'], count: $count), FilterTypeRegistry::builtIn(), null)
            ->handle(new ListInput(page: $page, perPage: $perPage));
    }

    public function testExposesTheResultToPagerfanta(): void
    {
        $pager = ListResultAdapter::createPager($this->listResult('auto', 2));

        self::assertSame([['id' => 3], ['id' => 4]], [...$pager->getCurrentPageResults()]);
        self::assertSame(5, $pager->getNbResults());
        self::assertSame(3, $pager->getNbPages());
        self::assertSame(2, $pager->getCurrentPage());
        self::assertTrue($pager->hasPreviousPage());
        self::assertTrue($pager->hasNextPage());
    }

    public function testAPageBeyondTheEndIsAnEmptyPage(): void
    {
        $pager = ListResultAdapter::createPager($this->listResult('auto', 9));

        self::assertSame([], [...$pager->getCurrentPageResults()]);
        self::assertSame(5, $pager->getNbResults());
    }

    public function testCountNoneKeepsPreviousAndNextNavigation(): void
    {
        $first = ListResultAdapter::createPager($this->listResult('none', 1));
        $last = ListResultAdapter::createPager($this->listResult('none', 3));

        self::assertTrue($first->hasNextPage());
        self::assertFalse($first->hasPreviousPage());
        self::assertSame(3, $first->getNbResults(), 'one more than the page: a next page exists');
        self::assertFalse($last->hasNextPage());
        self::assertSame(5, $last->getNbResults());
        self::assertSame([['id' => 5]], [...$last->getCurrentPageResults()]);
    }

    public function testOnlyTheResultsOwnPageCanBeRead(): void
    {
        $adapter = new ListResultAdapter($this->listResult('auto', 1));

        $this->expectException(\LogicException::class);

        $adapter->getSlice(2, 2);
    }
}
