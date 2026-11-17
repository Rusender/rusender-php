<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Campaign statistics.
 *
 * @property-read int         $total
 * @property-read StatMetric  $sending
 * @property-read StatMetric  $delivered
 * @property-read StatMetric  $open
 * @property-read StatMetric  $click
 * @property-read StatMetric  $error
 * @property-read StatMetric  $unsubscribe
 * @property-read StatMetric  $complaint
 */
final class CampaignStats extends ApiObject
{
    protected const NESTED = [
        'sending' => StatMetric::class,
        'delivered' => StatMetric::class,
        'open' => StatMetric::class,
        'click' => StatMetric::class,
        'error' => StatMetric::class,
        'unsubscribe' => StatMetric::class,
        'complaint' => StatMetric::class,
    ];
}
