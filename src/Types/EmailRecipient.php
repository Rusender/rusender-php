<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A recipient of an email.
 *
 * @property-read string                  $id
 * @property-read string                  $email
 * @property-read string|null             $name
 * @property-read string                  $type to, cc or bcc
 * @property-read string                  $status
 * @property-read EmailReason|null        $reason
 * @property-read \DateTimeImmutable|null $sentAt
 * @property-read \DateTimeImmutable|null $completedAt
 */
final class EmailRecipient extends ApiObject
{
    protected const NESTED = [
        'reason' => EmailReason::class,
    ];
}
