<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A variant of an A/B campaign.
 *
 * @property-read int                 $id
 * @property-read string              $name
 * @property-read string|null         $subject
 * @property-read string|null         $previewTitle
 * @property-read string              $status
 * @property-read float               $splitPercent
 * @property-read bool|null           $isWinner
 * @property-read CampaignStats|null  $stats
 */
final class AbTestVariant extends ApiObject
{
    protected const NESTED = [
        'stats' => CampaignStats::class,
    ];
}
