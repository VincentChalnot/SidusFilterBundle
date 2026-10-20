<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Definition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Definition\Field;

final class FieldTest extends TestCase
{
    public function testParsesJsonPathAndCastShorthand(): void
    {
        $field = Field::parse('totals->regression->score::int');

        self::assertSame('totals', $field->path);
        self::assertSame(['regression', 'score'], $field->json);
        self::assertSame('integer', $field->cast);
        self::assertSame('totals->regression->score::integer', (string) $field);
    }

    public function testSegments(): void
    {
        self::assertSame(['author', 'company', 'name'], Field::of('author.company.name')->segments());
    }

    public function testConfigMapAndArrayRoundTrip(): void
    {
        $field = Field::fromConfig(['path' => 'totals', 'json' => ['regression'], 'cast' => 'float']);

        self::assertEquals($field, Field::fromArray($field->toArray()));
    }

    #[DataProvider('invalid')]
    public function testRejectsInvalid(string|array $config): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Field::fromConfig($config);
    }

    public static function invalid(): iterable
    {
        yield 'empty' => [''];
        yield 'bad path' => ['a..b'];
        yield 'sql injection' => ['a; DROP TABLE x'];
        yield 'unknown cast' => ['a::money'];
        yield 'map without path' => [['json' => ['a']]];
        yield 'unknown key' => [['path' => 'a', 'foo' => 1]];
    }
}
