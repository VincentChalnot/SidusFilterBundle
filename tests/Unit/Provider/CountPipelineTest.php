<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\InMemory\ArraySource;
use Sidus\FilterBundle\InMemory\InMemoryContext;
use Sidus\FilterBundle\InMemory\InMemoryQueryHandlerFactory;
use Sidus\FilterBundle\Input\ListInput;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryContextInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

final class CountPipelineTest extends TestCase
{
    private function handle(string $count, ?CountStrategyInterface $strategy, ListInput $input): \Sidus\FilterBundle\Result\ListResult
    {
        $factory = InMemoryQueryHandlerFactory::standalone(['s' => new ArraySource([['id' => 1], ['id' => 2], ['id' => 3]])]);

        return $factory
            ->createQueryHandler(new ListDefinition('l', 'in_memory', ['source' => 's', 'identifier' => 'id'], perPage: 2, count: $count), FilterTypeRegistry::builtIn(), $strategy)
            ->handle($input);
    }

    public function testStrategyReceivesTheQueryContextTheDefinitionAndTheInput(): void
    {
        $strategy = new class() implements CountStrategyInterface {
            public ?QueryContextInterface $context = null;
            public ?string $code = null;
            public ?ListInput $input = null;

            public function count(QueryContextInterface $query, ListDefinition $list, ListInput $input): ?int
            {
                [$this->context, $this->code, $this->input] = [$query, $list->code, $input];

                return null;
            }
        };
        $input = new ListInput(page: 1);

        $result = $this->handle('app.count', $strategy, $input);

        self::assertNull($result->total(), 'a strategy may answer "unknown"');
        self::assertInstanceOf(InMemoryContext::class, $strategy->context);
        self::assertSame('l', $strategy->code);
        self::assertSame($input, $strategy->input);
        self::assertFalse($result->hasNextPage());
    }

    public function testMissingStrategyIsReportedClearly(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('uses the count strategy "app.count" but the handler was created without it');

        $this->handle('app.count', null, new ListInput());
    }

    public function testAutoCountIsLazy(): void
    {
        $result = $this->handle('auto', null, new ListInput());

        self::assertSame(3, $result->total());
        self::assertSame(2, $result->pages());
        self::assertTrue($result->hasNextPage());
    }

    public function testNoneHasNoTotalOrPageCount(): void
    {
        $result = $this->handle('none', null, new ListInput());

        self::assertNull($result->total());
        self::assertNull($result->pages());
        self::assertTrue($result->hasNextPage());
        self::assertCount(2, $result->items());
    }
}
