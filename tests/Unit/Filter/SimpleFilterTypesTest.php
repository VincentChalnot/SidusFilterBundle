<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\In;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Matches;
use Sidus\FilterBundle\Criterion\Native;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

final class SimpleFilterTypesTest extends FilterTypeTestCase
{
    public function testExact(): void
    {
        $filter = $this->definition('exact');

        self::assertEquals(new Equals('field', 'a'), $this->apply($filter, 'a'));
        self::assertEquals(new Equals('field', 0), $this->apply($filter, 0));
        self::assertNull($this->apply($filter, ''));
        self::assertSame([''], $this->apply($filter, ['a']));
    }

    public function testChoiceWithChoicesMapReturnsTypedValues(): void
    {
        $filter = $this->definition('choice', ['choices' => ['Draft' => 'draft', 'One' => 1]]);

        self::assertEquals(new Equals('field', 1), $this->apply($filter, '1'));
        self::assertSame([''], $this->apply($filter, 'nope'));
        self::assertSame([''], $this->apply($filter, ['draft']), 'several values need multiple: true');
    }

    public function testChoiceMultiple(): void
    {
        $filter = $this->definition('choice', ['choices' => ['a', 'b', 'c'], 'multiple' => true]);

        self::assertEquals(new In('field', ['a', 'c']), $this->apply($filter, ['a', 'c']));
        self::assertEquals(new In('field', ['b']), $this->apply($filter, 'b'));
        self::assertSame(['1'], $this->apply($filter, ['a', 'z']));
        self::assertNull($this->apply($filter, []));
        self::assertSame([In::class], $this->type('choice')->getRequiredOperations($filter));
    }

    public function testChoiceWithoutChoicesAcceptsAnyScalar(): void
    {
        self::assertEquals(new Equals('field', 'anything'), $this->apply($this->definition('choice'), 'anything'));
    }

    public function testNumber(): void
    {
        $filter = $this->definition('number', ['operators' => ['eq', 'gt', 'gte', 'lt', 'lte', 'neq', 'is_null']]);

        self::assertEquals(new Equals('field', 5), $this->apply($filter, '5'));
        self::assertEquals(new Equals('field', 1.5), $this->apply($filter, 1.5));
        self::assertEquals(new Range('field', min: 5, minInclusive: false), $this->apply($filter, ['operator' => 'gt', 'value' => '5']));
        self::assertEquals(new Range('field', min: 5), $this->apply($filter, ['operator' => 'gte', 'value' => 5]));
        self::assertEquals(new Range('field', max: 5, maxInclusive: false), $this->apply($filter, ['operator' => 'lt', 'value' => 5]));
        self::assertEquals(new Range('field', max: 5), $this->apply($filter, ['operator' => 'lte', 'value' => 5]));
        self::assertEquals(new Not(new Equals('field', 5)), $this->apply($filter, ['operator' => 'neq', 'value' => 5]));
        self::assertEquals(new IsNull('field'), $this->apply($filter, ['operator' => 'is_null']));
        self::assertSame(['value'], $this->apply($filter, ['operator' => 'gt', 'value' => 'abc']));
        self::assertSame(['value'], $this->apply($filter, ['operator' => 'gt', 'value' => 'INF']));
        self::assertSame(['value'], $this->apply($this->definition('number'), 'abc'));
    }

    public function testNullFlags(): void
    {
        self::assertEquals(new IsNull('field'), $this->apply($this->definition('null'), '1'));
        self::assertEquals(new Not(new IsNull('field')), $this->apply($this->definition('not_null'), true));
        self::assertEquals(new Not(new IsEmpty('field')), $this->apply($this->definition('not_empty'), 'on'));
        self::assertNull($this->apply($this->definition('null'), '0'));
        self::assertNull($this->apply($this->definition('null'), ''));
        self::assertNull($this->apply($this->definition('null'), false));
        self::assertSame([''], $this->apply($this->definition('null'), 'maybe'));
    }

    public function testRegexp(): void
    {
        $filter = $this->definition('regexp');

        self::assertEquals(new Matches('field', '^a.*z$', true), $this->apply($filter, '^a.*z$'));
        self::assertEquals(new Not(new Matches('field', 'x', false)), $this->apply($filter, ['operator' => 'not_imatches', 'value' => 'x']));
        self::assertSame([Matches::class, Not::class], $this->type('regexp')->getRequiredOperations($filter));
        foreach (['(?=a)', '\\d+', '(a)\\1', 'a++', '(', '[a'] as $bad) {
            self::assertSame(['value'], $this->apply($filter, ['operator' => 'matches', 'value' => $bad]), $bad);
        }
    }

    public function testNative(): void
    {
        $builder = new class() implements \Sidus\FilterBundle\Filter\NativeCriterionBuilderInterface {
            public function __invoke(FilterDefinition $filter, mixed $value): Native
            {
                return new Native('in_memory', $value);
            }
        };
        $types = FilterTypeRegistry::builtIn(new ServiceLocator(['b' => static fn () => $builder]));
        $filter = new FilterDefinition('n', 'native', options: ['builder' => 'b']);
        $type = $types->get('native');

        self::assertEquals(new Native('in_memory', 'x'), $type->buildCriterion($filter, $type->normalize($filter, 'x')->value));
        self::assertTrue($type->normalize($filter, '')->isEmpty());
        self::assertSame([Native::class], $type->getRequiredOperations($filter));
        $this->expectException(MissingOptionsException::class);
        $this->definition('native');
    }

    public function testNativeWithoutBuilderServiceFails(): void
    {
        $type = FilterTypeRegistry::builtIn()->get('native');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no builder service "b"');

        $type->buildCriterion(new FilterDefinition('n', 'native', options: ['builder' => 'b']), 'x');
    }
}
