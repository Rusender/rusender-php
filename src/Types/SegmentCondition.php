<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A condition of a segment.
 *
 * @property-read int          $id
 * @property-read string       $field contactName, contactEmail, contactStatus, variable, …
 * @property-read int|null     $variableId
 * @property-read string       $operator equal, contain, before, findInList, …
 * @property-read string|null  $value
 */
final class SegmentCondition extends ApiObject {}
