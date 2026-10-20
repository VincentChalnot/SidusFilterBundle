<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Unit\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Criterion\Contains;
use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Criterion\Not;
use Sidus\FilterBundle\Criterion\Sort;
use Sidus\FilterBundle\Definition\DefinitionResolver;
use Sidus\FilterBundle\Exception\ConfigurationException;
use Sidus\FilterBundle\Provider\Capabilities;
use Sidus\FilterBundle\Filter\Type\BuiltInFilterTypes;
use Sidus\FilterBundle\Tests\Support\FakeFactory;

final class DefinitionResolverTest extends TestCase
{
    private function resolver(?Capabilities $capabilities = null): DefinitionResolver
    {
        $capabilities ??= new Capabilities('fake', [Contains::class, Equals::class, Not::class, Sort::class]);

        return new DefinitionResolver(BuiltInFilterTypes::classes(), ['fake' => FakeFactory::class], ['fake' => $capabilities]);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $extra
     */
    private function config(array $filters, array $extra = []): array
    {
        return $extra + ['provider' => 'fake', 'options' => ['entity' => 'Post'], 'filters' => $filters];
    }

    public function testResolvesAValidList(): void
    {
        $list = $this->resolver()->resolve('posts', $this->config([
            'title' => ['type' => 'text', 'provider_options' => ['fake' => ['case_insensitive' => 'ilike'], 'other' => ['ignored' => true]], 'form' => ['label' => 'T']],
            'status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published']], 'default' => 'published', 'locked' => true],
            'sort' => ['type' => 'sort', 'options' => ['columns' => ['title' => 'title']]],
        ], ['pagination' => ['per_page' => 10, 'max_per_page' => 50], 'count' => 'none']));

        self::assertSame(['entity' => 'Post'], $list->options);
        self::assertSame([10, 50, 'none'], [$list->perPage, $list->maxPerPage, $list->count]);
        self::assertSame(['title', 'status', 'sort'], array_map(static fn ($f) => $f->key, $list->filters));
        self::assertSame(['case_insensitive' => 'ilike'], $list->filters[0]->getProviderOptions('fake'));
        self::assertSame(['label' => 'T'], $list->filters[0]->form);
        self::assertTrue($list->filters[1]->locked);
        self::assertSame(['contains'], $list->filters[0]->options['operators']);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('misconfigurations')]
    public function testMisconfigurationsFailWithAClearMessage(array $config, string $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/'.preg_quote($message, '/').'/');

        $this->resolver()->resolve('posts', $config);
    }

    public static function misconfigurations(): iterable
    {
        $c = static fn (array $filters, array $extra = []): array => $extra + ['provider' => 'fake', 'options' => ['entity' => 'Post'], 'filters' => $filters];

        yield 'unknown provider' => [['provider' => 'nope'], 'List "posts": unknown provider "nope"'];
        yield 'missing provider' => [[], 'the "provider" is required'];
        yield 'bad list options' => [$c([], ['options' => []]), 'List "posts": options: The required option "entity" is missing'];
        yield 'bad pagination' => [$c([], ['pagination' => ['per_page' => 20, 'max_per_page' => 10]]), 'pagination requires'];
        yield 'bad count' => [$c([], ['count' => '']), '"count" must be'];
        yield 'unknown type' => [$c(['t' => ['type' => 'nope']]), 'List "posts", filter "t": Unknown filter type "nope"'];
        yield 'missing type' => [$c(['t' => []]), 'filter "t": the "type" is required'];
        yield 'bad filter options' => [$c(['t' => ['type' => 'text', 'options' => ['operators' => ['like']]]]), 'filter "t" (type "text"): options:'];
        yield 'bad provider options' => [$c(['t' => ['type' => 'text', 'provider_options' => ['fake' => ['case_insensitive' => 'x']]]]), 'provider_options.fake'];
        yield 'bad attribute' => [$c(['t' => ['type' => 'text', 'attributes' => ['a b']]]), 'Invalid field path'];
        yield 'locked without default' => [$c(['t' => ['type' => 'text', 'locked' => true]]), 'locked filter requires a "default"'];
        yield 'duplicate public name' => [
            $c(['title' => ['type' => 'text'], 'q' => ['type' => 'text', 'name' => 'title']]),
            'filters "title" and "q" have the same public name "title"',
        ];
        yield 'two sorts' => [
            $c(['s1' => ['type' => 'sort', 'options' => ['columns' => ['a' => 'a']]], 's2' => ['type' => 'sort', 'name' => 's2', 'options' => ['columns' => ['a' => 'a']]]]),
            'at most one sort filter is allowed, found: s1, s2',
        ];
        yield 'unsupported criterion' => [
            $c(['t' => ['type' => 'text', 'options' => ['operators' => ['contains', 'starts_with']]]]),
            'criterion Sidus\FilterBundle\Criterion\StartsWith is not supported: provider "fake" has no compiler for it',
        ];
    }

    public function testNarrowingOperatorsAvoidsTheRefusal(): void
    {
        $list = $this->resolver()->resolve('posts', $this->config(['t' => ['type' => 'text', 'options' => ['operators' => ['contains']]]]));

        self::assertCount(1, $list->filters);
    }

    public function testFilterRestrictedToAnotherProviderIsIgnoredEntirely(): void
    {
        $list = $this->resolver()->resolve('posts', $this->config([
            'title_ft' => ['name' => 'title', 'type' => 'es_match_does_not_exist', 'providers' => ['elasticsearch'], 'options' => ['nonsense' => 1]],
            'title_like' => ['name' => 'title', 'type' => 'text', 'providers' => ['fake', 'in_memory']],
            'status' => ['type' => 'exact'],
        ]));

        self::assertSame(['title_like', 'status'], array_map(static fn ($f) => $f->key, $list->filters));
        self::assertSame('title', $list->filters[0]->publicName());
    }

    public function testExplicitRefusalReasonWinsOverCompilerPresence(): void
    {
        $resolver = $this->resolver(new Capabilities('fake', [Contains::class], [Contains::class => 'field "title" is analysed text']));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('field "title" is analysed text');

        $resolver->resolve('posts', $this->config(['t' => ['type' => 'text']]));
    }
}
