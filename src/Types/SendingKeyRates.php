<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Rates of sending key statistics.
 *
 * @property-read float  $openRate
 * @property-read float  $clickRate
 * @property-read float  $errorRate
 * @property-read float  $unsubscribeAndComplaintRate
 */
final class SendingKeyRates extends ApiObject {}
