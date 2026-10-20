<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use Sidus\FilterBundle\Criterion\AnyOf;
use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\StartsWith;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TextFilterTypeTest extends FilterTypeTestCase
{
    public function testDefaultOperatorIsContains(): void
    {
        self::assertEquals(new Contains('field', 'abc', false), $this->apply($this->definition('text'), 'abc'));
    }

    public function testBlankIsInactive(): void
    {
        foreach ([null, '', []] as $blank) {
            self::assertNull($this->apply($this->definition('text'), $blank));
        }
    }

    #[DataProvider('operators')]
    public function testOperators(string $operator, mixed $expected): void
    {
        $filter = $this->definition('text', ['operators' => ['contains', 'starts_with', 'not_starts_with', 'equals', 'not_equals', 'is_null', 'is_not_empty', 'not_contains']]);

        self::assertEquals($expected, $this->apply($filter, ['operator' => $operator, 'value' => 'x']));
    }

    public static function operators(): iterable
    {
        yield ['starts_with', new StartsWith('field', 'x')];
        yield ['not_starts_with', new Not(new StartsWith('field', 'x'))];
        yield ['equals', new Equals('field', 'x')];
        yield ['not_equals', new Not(new Equals('field', 'x'))];
        yield ['is_null', new IsNull('field')];
        yield ['is_not_empty', new Not(new IsEmpty('field'))];
        yield ['not_contains', new Not(new Contains('field', 'x'))];
    }

    public function testBareStringUsesTheFirstConfiguredOperator(): void
    {
        $filter = $this->definition('text', ['operators' => ['starts_with', 'contains']]);

        self::assertEquals(new StartsWith('field', 'x'), $this->apply($filter, 'x'));
    }

    public function testViolations(): void
    {
        $filter = $this->definition('text', ['operators' => ['contains']]);

        self::assertSame(['operator'], $this->apply($filter, ['operator' => 'equals', 'value' => 'x']));
        self::assertSame(['value'], $this->apply($filter, ['operator' => 'contains', 'value' => ['a']]));
        self::assertSame([''], $this->apply($filter, ['x', 'y']));
    }

    public function testSeveralAttributesAreOred(): void
    {
        $filter = $this->definition('text', attributes: ['a', 'b']);

        self::assertEquals(new AnyOf(new Contains('a', 'x'), new Contains('b', 'x')), $this->apply($filter, 'x'));
        self::assertContains(AnyOf::class, $this->type('text')->getRequiredOperations($filter));
    }

    public function testRequiredOperationsFollowTheOperators(): void
    {
        $filter = $this->definition('text', ['operators' => ['contains', 'not_equals']]);

        self::assertEqualsCanonicalizing([Contains::class, Equals::class, Not::class], $this->type('text')->getRequiredOperations($filter));
    }

    public function testUnknownOperatorIsAConfigurationError(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->definition('text', ['operators' => ['like']]);
    }

    public function testCaseSensitiveOption(): void
    {
        self::assertEquals(new Contains('field', 'x', true), $this->apply($this->definition('text', ['case_sensitive' => true]), 'x'));
    }
}
