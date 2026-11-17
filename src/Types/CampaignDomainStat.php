<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Campaign statistics for one recipient mailbox domain.
 *
 * @property-read string       $groupName
 * @property-read string|null  $groupSlug
 * @property-read string|null  $iconBase64
 * @property-read string       $domain
 * @property-read int          $total
 * @property-read StatMetric   $open
 * @property-read StatMetric   $click
 * @property-read StatMetric   $error
 * @property-read StatMetric   $unsubscribe
 */
final class CampaignDomainStat extends ApiObject
{
    protected const NESTED = [
        'open' => StatMetric::class,
        'click' => StatMetric::class,
        'error' => StatMetric::class,
        'unsubscribe' => StatMetric::class,
    ];
}
