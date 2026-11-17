<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An event of a recipient.
 *
 * @property-read string             $type suppressed, delivered, bounced, failed, opened, clicked, unsubscribed or complained
 * @property-read \DateTimeImmutable $occurredAt
 * @property-read string|null        $url
 */
final class EmailEvent extends ApiObject {}
