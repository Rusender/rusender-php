<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A contact segment built from conditions.
 *
 * @property-read int                     $id
 * @property-read string                  $name
 * @property-read string                  $applyConditionsType and or or
 * @property-read list<SegmentCondition>  $conditions
 * @property-read \DateTimeImmutable      $createdAt
 * @property-read \DateTimeImmutable      $updatedAt
 * @property-read string                  $dashboardUrl
 */
final class Segment extends ApiObject
{
    protected const NESTED = [
        'conditions' => [SegmentCondition::class],
    ];
}
