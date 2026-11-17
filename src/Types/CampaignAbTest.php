<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The A/B test of a campaign.
 *
 * @property-read int                  $basicId
 * @property-read float                $testPercent
 * @property-read string               $experimentType subject or template
 * @property-read bool                 $isWinnerSelected
 * @property-read int|null             $winnerId
 * @property-read list<AbTestVariant>  $variants
 */
final class CampaignAbTest extends ApiObject
{
    protected const NESTED = [
        'variants' => [AbTestVariant::class],
    ];
}
