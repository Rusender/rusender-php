<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A recipient of an email with its events.
 *
 * @property-read string                  $id
 * @property-read string                  $email
 * @property-read string|null             $name
 * @property-read string                  $type to, cc or bcc
 * @property-read string                  $status
 * @property-read EmailReason|null        $reason
 * @property-read \DateTimeImmutable|null $sentAt
 * @property-read \DateTimeImmutable|null $completedAt
 * @property-read list<EmailEvent>        $events
 */
final class EmailRecipientWithEvents extends ApiObject
{
    protected const NESTED = [
        'reason' => EmailReason::class,
        'events' => [EmailEvent::class],
    ];
}
