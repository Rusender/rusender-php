<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An event of a recipient with the device and client of opens and clicks.
 *
 * @property-read string             $type
 * @property-read \DateTimeImmutable $occurredAt
 * @property-read string|null        $url
 * @property-read string|null        $device desktop, phone or tablet
 * @property-read string|null        $client outlook, chrome, firefox, safari, apple_mail or other
 */
final class EmailRecipientEvent extends ApiObject {}
