<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A count with its rate.
 *
 * @property-read int    $count
 * @property-read float  $rate share from 0 to 1: sending, delivery and errors of all recipients; opens, unsubscribes and complaints of delivered; clicks of opens
 */
final class StatMetric extends ApiObject {}
