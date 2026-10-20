<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\Field;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;

final class DefinitionTest extends TestCase
{
    public function testAttributesDefaultToTheKeyAndNameToTheKey(): void
    {
        $filter = new FilterDefinition('title', 'text');

        self::assertEquals([new Field('title')], $filter->attributes);
        self::assertSame('title', $filter->publicName());
    }

    public function testProviderRestriction(): void
    {
        $filter = new FilterDefinition('t', 'text', providers: ['doctrine']);

        self::assertTrue($filter->isActiveFor('doctrine'));
        self::assertFalse($filter->isActiveFor('in_memory'));
        self::assertTrue(new FilterDefinition('t', 'text')->isActiveFor('anything'));
    }

    public function testListRoundTripsThroughArrays(): void
    {
        $list = new ListDefinition(
            'posts',
            'in_memory',
            ['source' => 'posts'],
            10,
            50,
            'none',
            [
                new FilterDefinition('title', 'text', ['author.name', 'title'], name: 'q', options: ['operators' => ['contains']], providerOptions: ['doctrine' => ['x' => 1]], default: 'a', form: ['label' => 'T']),
                new FilterDefinition('status', 'choice', locked: true, default: 'published'),
            ],
        );

        self::assertEquals($list, ListDefinition::fromArray($list->toArray()));
        self::assertSame($list->filters[0], $list->findFilter('q'));
        self::assertNull($list->findFilter('title'));
        self::assertSame(['x' => 1], $list->filters[0]->getProviderOptions('doctrine'));
    }

    public function testDuplicatePublicNameIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate public filter name "title"');

        new ListDefinition('posts', 'x', filters: [new FilterDefinition('title', 'text'), new FilterDefinition('t2', 'text', name: 'title')]);
    }

    public function testPaginationBounds(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ListDefinition('posts', 'x', perPage: 200, maxPerPage: 100);
    }
}
