<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Test\Contract;

/**
 * The shared fixture of the provider contract suite, defined once as plain arrays: authors, posts with a to-one
 * author (or none), to-many tags, nulls, empty strings, mixed case, Unicode, wildcard characters and equal sort keys.
 * Every provider loads it into its own storage (objects, SQLite schema, an Elasticsearch index...).
 */
final class FixtureDataset
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public static function authors(): array
    {
        return [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'bob'],
            ['id' => 3, 'name' => 'Émile'],
        ];
    }

    /**
     * @return list<array{id: int, title: string, status: string, views: int|null, publishedAt: \DateTimeImmutable|null, summary: string|null, author: array{id: int, name: string}|null, tags: list<array{id: int, label: string}>}>
     */
    public static function posts(): array
    {
        $authors = array_column(self::authors(), null, 'id');
        $tag = static fn (int $id, string $label): array => ['id' => $id, 'label' => $label];
        $at = static fn (string $date): \DateTimeImmutable => new \DateTimeImmutable($date);

        return [
            ['id' => 1, 'title' => 'Hello World', 'status' => 'published', 'views' => 100, 'publishedAt' => $at('2026-01-10T12:00:00+00:00'), 'summary' => 'First post', 'author' => $authors[1], 'tags' => [$tag(1, 'php'), $tag(2, 'symfony')]],
            ['id' => 2, 'title' => 'hello again', 'status' => 'draft', 'views' => 20, 'publishedAt' => $at('2026-02-01T09:00:00+00:00'), 'summary' => '', 'author' => $authors[2], 'tags' => [$tag(3, 'php')]],
            ['id' => 3, 'title' => 'Über cool', 'status' => 'published', 'views' => null, 'publishedAt' => null, 'summary' => null, 'author' => $authors[1], 'tags' => []],
            ['id' => 4, 'title' => 'Zoë and friends', 'status' => 'published', 'views' => 100, 'publishedAt' => $at('2026-01-10T12:00:00+00:00'), 'summary' => '100% sure_thing', 'author' => null, 'tags' => [$tag(4, 'symfony'), $tag(5, '100%')]],
            ['id' => 5, 'title' => 'ÉCOLE', 'status' => 'archived', 'views' => 5, 'publishedAt' => $at('2025-12-31T23:30:00+00:00'), 'summary' => 'école', 'author' => $authors[3], 'tags' => [$tag(6, 'php'), $tag(7, 'sym_fony')]],
            ['id' => 6, 'title' => 'under_score', 'status' => 'draft', 'views' => 250, 'publishedAt' => $at('2026-03-01T00:00:00+00:00'), 'summary' => 'a*b', 'author' => $authors[2], 'tags' => [$tag(8, 'symfony')]],
        ];
    }

    /**
     * The filters of the fixture list, by key (= public name). Cases replace or remove entries.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function filterSpecs(): array
    {
        $text = static fn (array $operators, array $attributes = []): array => ['type' => 'text', 'options' => ['operators' => $operators]] + ([] === $attributes ? [] : ['attributes' => $attributes]);

        return [
            'title' => $text(['contains', 'starts_with', 'ends_with', 'equals']),
            'title_cs' => ['type' => 'text', 'attributes' => ['title'], 'options' => ['case_sensitive' => true]],
            'q' => ['type' => 'text', 'attributes' => ['title', 'author.name']],
            'summary' => $text(['contains', 'not_contains', 'is_empty', 'is_not_empty']),
            'summary_not_empty' => ['type' => 'not_empty', 'attributes' => ['summary']],
            'author_name' => ['type' => 'text', 'attributes' => ['author.name']],
            'views' => ['type' => 'number', 'options' => ['operators' => ['eq', 'neq', 'gt', 'gte', 'lt', 'lte']]],
            'views_null' => ['type' => 'null', 'attributes' => ['views']],
            'views_not_null' => ['type' => 'not_null', 'attributes' => ['views']],
            'author_null' => ['type' => 'null', 'attributes' => ['author']],
            'author_not_null' => ['type' => 'not_null', 'attributes' => ['author']],
            'published' => ['type' => 'date_range', 'attributes' => ['publishedAt']],
            'status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published', 'archived'], 'multiple' => true]],
            'status_exact' => ['type' => 'exact', 'attributes' => ['status']],
            'title_regexp' => ['type' => 'regexp', 'attributes' => ['title']],
            'summary_regexp' => ['type' => 'regexp', 'attributes' => ['summary']],
            'tag' => ['type' => 'text', 'attributes' => ['tags.label']],
            'tag_a' => ['type' => 'exact', 'attributes' => ['tags.label']],
            'tag_b' => ['type' => 'exact', 'attributes' => ['tags.label']],
            'tags_empty' => $text(['is_empty', 'is_not_empty'], ['tags']),
            'sort' => ['type' => 'sort', 'options' => [
                'columns' => ['id' => 'id', 'title' => 'title', 'views' => 'views', 'published' => 'publishedAt', 'author' => 'author.name', 'status' => 'status'],
                'default' => [['column' => 'id', 'direction' => 'asc']],
            ]],
        ];
    }
}
