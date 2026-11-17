<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The base campaign of a part.
 *
 * @property-read int                 $id
 * @property-read string              $type
 * @property-read string              $name
 * @property-read string|null         $subject
 * @property-read string|null         $previewTitle
 * @property-read string              $status
 * @property-read CampaignStats|null  $stats
 */
final class CampaignParent extends ApiObject
{
    protected const NESTED = [
        'stats' => CampaignStats::class,
    ];
}
