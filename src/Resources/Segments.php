<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\Page;
use RuSender\Types\Segment;
use RuSender\Types\SegmentFilter;
use RuSender\Types\SegmentFilterValue;

/**
 * Contact segments built from conditions.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type ConditionInput array{field: string, operator: string, id?: int, variableId?: int, value?: string}
 * @phpstan-type CreateSegmentParams array{name: string, applyConditionsType?: 'and'|'or', conditions?: list<ConditionInput>}
 * @phpstan-type UpdateSegmentParams array{name?: string, applyConditionsType?: 'and'|'or', conditions?: list<ConditionInput>}
 */
final class Segments extends AbstractResource
{
    /**
     * Lists segments with their conditions, searchable by name.
     * Scope: `segments.read`.
     *
     * @param array{page?: int, limit?: int, name?: string} $params
     * @param RequestOptions                                 $options
     *
     * @return Page<Segment>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/segments', $params), $options, Segment::class);
    }

    /**
     * Creates a segment from conditions joined with `applyConditionsType` (`and` / `or`). A condition is a field
     * (`contactEmail`, `variable` with `variableId`, …), an operator and a value; listFilters() shows what fields
     * support.
     * Scope: `segments.write`.
     *
     * @param CreateSegmentParams $params
     * @param RequestOptions      $options
     */
    public function create(array $params, array $options = []): Segment
    {
        return $this->call(Request::withBody('POST', '/v1/public/segments', $params), $options, Segment::class);
    }

    /**
     * Gets a segment with all its conditions.
     * Scope: `segments.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $segmentId, array $options = []): Segment
    {
        return $this->call(Request::get(Path::build('/v1/public/segments/{}', $segmentId)), $options, Segment::class);
    }

    /**
     * Updates a segment. A passed `conditions` becomes the new set: conditions with `id` are kept and changed,
     * without `id` — added, the ones not listed — removed; `[]` removes all. Without `conditions` they stay as is.
     * Scope: `segments.write`.
     *
     * @param UpdateSegmentParams $params
     * @param RequestOptions      $options
     */
    public function update(int $segmentId, array $params, array $options = []): Segment
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/segments/{}', $segmentId), $params), $options, Segment::class);
    }

    /**
     * Lists the fields available for conditions, including list variables, with the operators each field supports.
     * Scope: `segments.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<SegmentFilter>
     */
    public function listFilters(array $options = []): array
    {
        return $this->items(Request::get('/v1/public/segments/filters'), $options, SegmentFilter::class);
    }

    /**
     * Lists selectable values of a field with a fixed set of values — currently `emailProvider` (mail providers).
     * Scope: `segments.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<SegmentFilterValue>
     */
    public function listFilterValues(string $field, array $options = []): array
    {
        return $this->items(Request::get(Path::build('/v1/public/segments/filters/{}/values', $field)), $options, SegmentFilterValue::class);
    }
}
