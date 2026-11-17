<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A field available for conditions.
 *
 * @property-read string                       $field
 * @property-read int|null                     $variableId
 * @property-read string|null                  $label
 * @property-read list<SegmentFilterOperator>  $operators
 */
final class SegmentFilter extends ApiObject
{
    protected const NESTED = [
        'operators' => [SegmentFilterOperator::class],
    ];
}
