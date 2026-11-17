<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * One recipient of any email, see emails->listRecipients().
 *
 * @property-read string|null             $id
 * @property-read string|null             $emailId
 * @property-read string                  $email
 * @property-read string|null             $name
 * @property-read string|null             $type
 * @property-read string                  $status
 * @property-read EmailReason|null        $reason
 * @property-read int                     $keyId
 * @property-read EmailSender             $from
 * @property-read string|null             $subject
 * @property-read \DateTimeImmutable      $createdAt
 * @property-read \DateTimeImmutable|null $sentAt
 * @property-read \DateTimeImmutable|null $completedAt
 */
final class Delivery extends ApiObject
{
    protected const NESTED = [
        'reason' => EmailReason::class,
        'from' => EmailSender::class,
    ];
}
