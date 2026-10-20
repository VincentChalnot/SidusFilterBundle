<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Test\Contract;

/**
 * The cases of the provider contract suite, as data: input, then the expected ordered post ids, total and violation
 * paths. Meaning is the in-memory reference (section 4 of the design); ids refer to {@see FixtureDataset::posts()}.
 */
final class ContractCases
{
    /**
     * @return array<string, array{input: array<string, mixed>, ids: list<int>, total: int, violations: list<string>, specs: array<string, array<string, mixed>|null>, pagination: array<string, int>}>
     */
    public static function all(): array
    {
        $cases = [];
        $add = static function (string $id, array $filters, array $ids, array $extra = []) use (&$cases): void {
            $input = ['filters' => $filters] + ($extra['input'] ?? []);
            $cases[$id] = [
                'input' => $input,
                'ids' => $ids,
                'total' => $extra['total'] ?? \count($ids),
                'violations' => $extra['violations'] ?? [],
                'specs' => $extra['specs'] ?? [],
                'pagination' => $extra['pagination'] ?? [],
            ];
        };
        $sorted = static fn (string $sort, array $ids): array => [['sort' => $sort], $ids];

        $add('no_input_default_sort', [], [1, 2, 3, 4, 5, 6]);

        // Text: case folding, Unicode, literal wildcards, operators.
        $add('contains_is_case_insensitive', ['title' => 'hello'], [1, 2]);
        $add('contains_case_sensitive_upper', ['title_cs' => 'Hello'], [1]);
        $add('contains_case_sensitive_lower', ['title_cs' => 'hello'], [2]);
        $add('contains_unicode_lowercase_fold_accented', ['title' => 'école'], [5]);
        $add('contains_unicode_lowercase_fold_umlaut', ['title' => 'ÜBER'], [3]);
        $add('contains_does_not_fold_accents', ['title' => 'ecole'], []);
        $add('contains_percent_is_literal', ['summary' => '%'], [4]);
        $add('contains_underscore_is_literal', ['summary' => '_'], [4]);
        $add('contains_star_is_literal', ['summary' => '*'], [6]);
        $add('starts_with', ['title' => ['operator' => 'starts_with', 'value' => 'hel']], [1, 2]);
        $add('ends_with', ['title' => ['operator' => 'ends_with', 'value' => 'ORE']], [6]);
        $add('equals_is_case_sensitive_match', ['title' => ['operator' => 'equals', 'value' => 'hello again']], [2]);
        $add('equals_is_case_sensitive_miss', ['title' => ['operator' => 'equals', 'value' => 'Hello again']], []);
        $add('equals_unicode', ['title' => ['operator' => 'equals', 'value' => 'ÉCOLE']], [5]);
        $add('several_attributes_are_ored_author', ['q' => 'bob'], [2, 6]);
        $add('several_attributes_are_ored_both', ['q' => 'alice'], [1, 3]);
        $add('not_contains_is_true_for_null_and_empty', ['summary' => ['operator' => 'not_contains', 'value' => 'first']], [2, 3, 4, 5, 6]);

        // Emptiness and nulls.
        $add('is_empty_means_null_or_empty_string', ['summary' => ['operator' => 'is_empty']], [2, 3]);
        $add('is_not_empty', ['summary' => ['operator' => 'is_not_empty']], [1, 4, 5, 6]);
        $add('not_empty_filter', ['summary_not_empty' => '1'], [1, 4, 5, 6]);
        $add('null_flag_scalar', ['views_null' => '1'], [3]);
        $add('not_null_flag_scalar', ['views_not_null' => '1'], [1, 2, 4, 5, 6]);
        $add('null_flag_to_one_relation', ['author_null' => '1'], [4]);
        $add('not_null_flag_to_one_relation', ['author_not_null' => '1'], [1, 2, 3, 5, 6]);
        $add('flag_off_is_inactive', ['views_null' => '0'], [1, 2, 3, 4, 5, 6]);

        // Numbers: null never matches a comparison, Not(Equals) is true for null.
        $add('number_gt', ['views' => ['operator' => 'gt', 'value' => 20]], [1, 4, 6]);
        $add('number_gte', ['views' => ['operator' => 'gte', 'value' => 100]], [1, 4, 6]);
        $add('number_lt_excludes_null', ['views' => ['operator' => 'lt', 'value' => 100]], [2, 5]);
        $add('number_lte', ['views' => ['operator' => 'lte', 'value' => 20]], [2, 5]);
        $add('number_eq', ['views' => ['operator' => 'eq', 'value' => 100]], [1, 4]);
        $add('number_eq_from_string', ['views' => '20'], [2]);
        $add('number_neq_includes_null', ['views' => ['operator' => 'neq', 'value' => 100]], [2, 3, 5, 6]);

        // Dates: instants, whole days for date-only bounds, inclusive.
        $add('date_range_whole_month', ['published' => ['from' => '2026-01-01', 'to' => '2026-01-31']], [1, 4]);
        $add('date_range_single_day', ['published' => ['from' => '2026-02-01', 'to' => '2026-02-01']], [2]);
        $add('date_range_from_only', ['published' => ['from' => '2026-03-01']], [6]);
        $add('date_range_to_only_end_of_day', ['published' => ['to' => '2025-12-31']], [5]);
        $add('date_range_exact_instant', ['published' => ['from' => '2026-01-10T12:00:00+00:00', 'to' => '2026-01-10T12:00:00+00:00']], [1, 4]);
        $add('date_range_other_offset_same_instant', ['published' => ['from' => '2026-01-10T13:00:00+01:00', 'to' => '2026-01-10T13:00:00+01:00']], [1, 4]);

        // Choices.
        $add('choice_single', ['status' => ['draft']], [2, 6]);
        $add('choice_multiple', ['status' => ['draft', 'archived']], [2, 5, 6]);
        $add('choice_scalar_with_multiple', ['status' => 'published'], [1, 3, 4]);
        $add('exact', ['status_exact' => 'published'], [1, 3, 4]);
        $add('choice_invalid_value_is_a_violation', ['status' => 'nope'], [1, 2, 3, 4, 5, 6], ['violations' => ['filters.status.0']]);

        // Regexp: unanchored, null and empty never match, so Not(Matches) is true for them.
        $add('regexp_matches_case_sensitive', ['title_regexp' => ['operator' => 'matches', 'value' => '^h']], [2]);
        $add('regexp_imatches', ['title_regexp' => ['operator' => 'imatches', 'value' => '^h']], [1, 2]);
        $add('regexp_not_matches', ['title_regexp' => ['operator' => 'not_matches', 'value' => '^h']], [1, 3, 4, 5, 6]);
        $add('regexp_unanchored', ['title_regexp' => ['operator' => 'matches', 'value' => 'o.*d$']], [1]);
        $add('regexp_posix_class', ['summary_regexp' => ['operator' => 'matches', 'value' => '^[[:digit:]]+%']], [4]);
        $add('regexp_not_matches_includes_null', ['summary_regexp' => ['operator' => 'not_matches', 'value' => 'zzz']], [1, 2, 3, 4, 5, 6]);
        $add('regexp_outside_the_portable_subset', ['title_regexp' => ['operator' => 'matches', 'value' => '(?=h)h']], [1, 2, 3, 4, 5, 6], ['violations' => ['filters.title_regexp.value']]);

        // To-many: "any element", independent per criterion.
        $add('to_many_any_element_contains', ['tag' => 'sym'], [1, 4, 5, 6]);
        $add('to_many_exact', ['tag_a' => 'php'], [1, 2, 5]);
        $add('to_many_two_criteria_are_independent', ['tag_a' => 'php', 'tag_b' => 'symfony'], [1]);
        $add('to_many_independent_other_pair', ['tag_a' => 'symfony', 'tag_b' => '100%'], [4]);
        $add('to_many_percent_is_literal', ['tag' => '%'], [4]);
        $add('to_many_underscore_is_literal', ['tag' => '_'], [5]);
        $add('to_many_is_empty', ['tags_empty' => ['operator' => 'is_empty']], [3]);
        $add('to_many_is_not_empty', ['tags_empty' => ['operator' => 'is_not_empty']], [1, 2, 4, 5, 6]);

        // To-one path.
        $add('to_one_path_contains', ['author_name' => 'li'], [1, 3]);

        // Sorting: nulls first ascending / last descending, byte-wise strings, identifier tie-breaker.
        $add('sort_title_ascending_is_byte_wise', [], [1, 4, 2, 6, 5, 3], ['input' => ['sort' => 'title']]);
        $add('sort_views_ascending_nulls_first_ties_by_id', [], [3, 5, 2, 1, 4, 6], ['input' => ['sort' => 'views']]);
        $add('sort_views_descending_nulls_last_ties_by_id', [], [6, 1, 4, 2, 5, 3], ['input' => ['sort' => '-views']]);
        $add('sort_date_descending', [], [6, 2, 1, 4, 5, 3], ['input' => ['sort' => '-published']]);
        $add('sort_two_columns', [], [5, 6, 2, 1, 4, 3], ['input' => ['sort' => 'status,-views']]);
        $add('sort_through_to_one_nulls_first', [], [4, 1, 3, 2, 6, 5], ['input' => ['sort' => 'author']]);
        $add('sort_structured_input', [], [6, 5, 4, 3, 2, 1], ['input' => ['sort' => [['column' => 'id', 'direction' => 'desc']]]]);
        $add('sort_with_a_filter', ['views_not_null' => '1'], [6, 4, 1, 2, 5], ['input' => ['sort' => '-views,-id']]);
        $add('sort_unknown_column_falls_back_to_default', [], [1, 2, 3, 4, 5, 6], ['input' => ['sort' => 'nope'], 'violations' => ['filters.sort.0']]);
        $add('sort_property_path_is_not_a_key', [], [1, 2, 3, 4, 5, 6], ['input' => ['sort' => 'author.name'], 'violations' => ['filters.sort.0']]);

        // Pagination.
        $add('page_two', [], [3, 4], ['input' => ['page' => 2, 'per_page' => 2], 'total' => 6]);
        $add('page_last_partial', [], [5, 6], ['input' => ['page' => '3', 'per_page' => '2'], 'total' => 6]);
        $add('page_beyond_the_end_is_empty', [], [], ['input' => ['page' => 4, 'per_page' => 2], 'total' => 6]);
        $add('page_with_filter_total_counts_matches', ['status' => ['draft', 'published']], [4, 6], ['input' => ['page' => 2, 'per_page' => 3], 'total' => 5]);
        $add('default_per_page_from_definition', [], [1, 2, 3], ['pagination' => ['per_page' => 3, 'max_per_page' => 50], 'total' => 6]);
        $add('invalid_page_is_a_violation', [], [1, 2, 3, 4, 5, 6], ['input' => ['page' => 'abc'], 'violations' => ['page']]);
        $add('page_zero_is_a_violation', [], [1, 2, 3, 4, 5, 6], ['input' => ['page' => 0], 'violations' => ['page']]);
        $add('per_page_above_the_maximum_is_a_violation', [], [1, 2, 3, 4, 5, 6], ['input' => ['per_page' => 51], 'violations' => ['per_page']]);

        // Invalid filter input: reported, the filter is inactive.
        $add('invalid_number_is_a_violation', ['views' => ['operator' => 'gt', 'value' => 'abc']], [1, 2, 3, 4, 5, 6], ['violations' => ['filters.views.value']]);
        $add('invalid_date_is_a_violation', ['published' => ['from' => 'yesterday']], [1, 2, 3, 4, 5, 6], ['violations' => ['filters.published.from']]);

        // Defaults and locked filters.
        $statusDefault = ['status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published', 'archived']], 'default' => 'draft']];
        $statusLocked = ['status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published', 'archived']], 'default' => 'published', 'locked' => true]];
        $add('default_applies_when_absent', [], [2, 6], ['specs' => $statusDefault]);
        $add('explicit_input_overrides_the_default', ['status' => 'archived'], [5], ['specs' => $statusDefault]);
        $add('explicit_blank_clears_the_default', ['status' => ''], [1, 2, 3, 4, 5, 6], ['specs' => $statusDefault]);
        $add('invalid_input_falls_back_to_the_default', ['status' => 'nope'], [2, 6], ['specs' => $statusDefault, 'violations' => ['filters.status']]);
        $add('locked_ignores_the_input', ['status' => 'draft'], [1, 3, 4], ['specs' => $statusLocked]);
        $add('locked_applies_without_input', [], [1, 3, 4], ['specs' => $statusLocked]);

        return $cases;
    }
}
