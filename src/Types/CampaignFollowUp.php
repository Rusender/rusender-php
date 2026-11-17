<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The follow-up of a campaign.
 *
 * @property-read int                 $id
 * @property-read string              $name
 * @property-read string|null         $subject
 * @property-read string|null         $previewTitle
 * @property-read string              $status
 * @property-read int                 $offsetHours
 * @property-read CampaignStats|null  $stats
 */
final class CampaignFollowUp extends ApiObject
{
    protected const NESTED = [
        'stats' => CampaignStats::class,
    ];
}
