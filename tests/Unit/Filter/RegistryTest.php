<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Exception\UnknownFilterTypeException;
use Sidus\FilterBundle\Filter\Type\TextFilterType;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

final class RegistryTest extends TestCase
{
    public function testIndexesByNameFromAnIteratorOnce(): void
    {
        $registry = FilterTypeRegistry::builtIn();

        self::assertTrue($registry->has('text'));
        self::assertInstanceOf(TextFilterType::class, $registry->get('text'));
        self::assertSame(['choice', 'date_range', 'exact', 'native', 'not_empty', 'not_null', 'null', 'number', 'regexp', 'sort', 'text'], $registry->names());
    }

    public function testUnknownTypeListsTheAvailableOnes(): void
    {
        $this->expectException(UnknownFilterTypeException::class);
        $this->expectExceptionMessage('Unknown filter type "nope", available: choice');

        FilterTypeRegistry::builtIn()->get('nope');
    }

    public function testDuplicateNamesAreRejected(): void
    {
        $registry = new FilterTypeRegistry([new TextFilterType(), new TextFilterType()]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('registered twice');

        $registry->names();
    }
}
