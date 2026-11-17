<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * One recipient of an email with its events.
 *
 * @property-read string                  $id
 * @property-read string                  $email
 * @property-read string|null             $name
 * @property-read string                  $type
 * @property-read string                  $status
 * @property-read EmailReason|null        $reason
 * @property-read \DateTimeImmutable|null $sentAt
 * @property-read \DateTimeImmutable|null $completedAt
 * @property-read list<EmailEvent>        $events
 * @property-read string                  $emailId
 * @property-read string|null             $smtpResponse
 * @property-read int                     $clickCount
 */
final class EmailRecipientDetails extends ApiObject
{
    protected const NESTED = [
        'reason' => EmailReason::class,
        'events' => [EmailEvent::class],
    ];
}
