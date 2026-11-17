<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Sending statistics of a key for a period.
 *
 * @property-read string            $dateFrom calendar day, YYYY-MM-DD
 * @property-read string            $dateTo calendar day, YYYY-MM-DD
 * @property-read SendingKeyTotals  $totals
 * @property-read SendingKeyRates   $rates
 */
final class SendingKeyStats extends ApiObject
{
    protected const NESTED = [
        'totals' => SendingKeyTotals::class,
        'rates' => SendingKeyRates::class,
    ];
}
