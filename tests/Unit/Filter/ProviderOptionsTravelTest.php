<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Filter;

use Sidus\FilterBundle\Criterion\AnyOf;
use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\FieldCriterion;
use Sidus\FilterBundle\Criterion\In;
use Sidus\FilterBundle\Criterion\IsEmpty;
use Sidus\FilterBundle\Criterion\IsNull;
use Sidus\FilterBundle\Criterion\Matches;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\Range;
use Sidus\FilterBundle\Criterion\StartsWith;
use Sidus\FilterBundle\Definition\FilterDefinition;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The emitting filter's `provider_options` travel with every criterion it builds, so a provider can realise two
 * filters on the same field differently.
 */
final class ProviderOptionsTravelTest extends FilterTypeTestCase
{
    private const array OPTIONS = ['doctrine' => ['case_insensitive' => 'ilike']];

    /**
     * @param array<string, mixed> $options
     */
    private function withProviderOptions(string $type, array $options = [], array $attributes = ['field']): FilterDefinition
    {
        $resolver = new OptionsResolver();
        $this->type($type)::configureOptions($resolver);

        return new FilterDefinition('f', $type, $attributes, options: $resolver->resolve($options), providerOptions: self::OPTIONS);
    }

    /**
     * @return list<FieldCriterion>
     */
    private function leaves(mixed $criterion): array
    {
        return match (true) {
            $criterion instanceof Not => $this->leaves($criterion->criterion),
            $criterion instanceof AnyOf => array_merge(...array_map($this->leaves(...), $criterion->criteria)),
            $criterion instanceof FieldCriterion => [$criterion],
            default => self::fail('unexpected criterion '.get_debug_type($criterion)),
        };
    }

    public function testEveryEmittingTypeCarriesTheProviderOptions(): void
    {
        $cases = [
            ['text', ['operators' => ['contains', 'starts_with', 'ends_with', 'equals', 'is_null', 'is_empty', 'not_contains']], ['operator' => 'contains', 'value' => 'x'], Contains::class],
            ['text', ['operators' => ['starts_with']], 'x', StartsWith::class],
            ['text', ['operators' => ['equals']], 'x', Equals::class],
            ['text', ['operators' => ['is_null']], ['operator' => 'is_null'], IsNull::class],
            ['text', ['operators' => ['is_empty']], ['operator' => 'is_empty'], IsEmpty::class],
            ['exact', [], 'x', Equals::class],
            ['choice', ['choices' => ['a', 'b']], 'a', Equals::class],
            ['choice', ['choices' => ['a', 'b'], 'multiple' => true], ['a'], In::class],
            ['number', ['operators' => ['gt']], 5, Range::class],
            ['number', ['operators' => ['eq']], 5, Equals::class],
            ['date_range', [], ['from' => '2026-01-01', 'to' => '2026-01-02'], Range::class],
            ['null', [], '1', IsNull::class],
            ['not_null', [], '1', IsNull::class],
            ['not_empty', [], '1', IsEmpty::class],
            ['regexp', [], 'a.*', Matches::class],
        ];
        foreach ($cases as [$type, $options, $raw, $class]) {
            $filter = $this->withProviderOptions($type, $options, ['a', 'b']);
            $leaves = $this->leaves($this->apply($filter, $raw));

            self::assertCount(2, $leaves, $type);
            foreach ($leaves as $leaf) {
                self::assertInstanceOf($class, $leaf, $type);
                self::assertSame(self::OPTIONS, $leaf->providerOptions, $type);
            }
        }
    }

    public function testTwoFiltersOnOneFieldKeepTheirOwnOptions(): void
    {
        $lower = new FilterDefinition('a', 'text', ['title'], options: ['operators' => ['contains'], 'case_sensitive' => false], providerOptions: ['doctrine' => ['case_insensitive' => 'lower']]);
        $ilike = new FilterDefinition('b', 'text', ['title'], options: ['operators' => ['contains'], 'case_sensitive' => false], providerOptions: ['doctrine' => ['case_insensitive' => 'ilike']]);

        $first = $this->apply($lower, 'x');
        $second = $this->apply($ilike, 'x');

        self::assertEquals(new Contains('title', 'x', false, ['doctrine' => ['case_insensitive' => 'lower']]), $first);
        self::assertEquals(new Contains('title', 'x', false, ['doctrine' => ['case_insensitive' => 'ilike']]), $second);
        self::assertSame('title', $first->field->path);
        self::assertEquals($first->field, $second->field, 'the field itself stays a plain identity');
    }

    public function testNoProviderOptionsMeansAnEmptyMap(): void
    {
        self::assertSame([], $this->apply($this->definition('text'), 'x')->providerOptions);
        self::assertSame([], new Equals('a', 1)->providerOptions);
    }
}
