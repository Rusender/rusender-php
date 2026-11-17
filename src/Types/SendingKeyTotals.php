<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Counts of sending key statistics.
 *
 * @property-read int  $all
 * @property-read int  $delivered
 * @property-read int  $opened
 * @property-read int  $clicked
 * @property-read int  $error
 * @property-read int  $softBounced
 * @property-read int  $hardBounced
 * @property-read int  $unsubscribed
 * @property-read int  $complaint
 */
final class SendingKeyTotals extends ApiObject {}
