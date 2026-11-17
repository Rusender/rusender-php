<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Contacts by status.
 *
 * @property-read int                 $total
 * @property-read array<string, int>  $byStatus
 * @property-read int                 $active
 * @property-read int                 $unsubscribed
 * @property-read int                 $unavailable
 */
final class ContactStatistics extends ApiObject {}
