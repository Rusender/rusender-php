<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An operator a filter field supports.
 *
 * @property-read string  $operator
 * @property-read string  $valueType string, number, date or none
 * @property-read bool    $valueRequired
 */
final class SegmentFilterOperator extends ApiObject {}
