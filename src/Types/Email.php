<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An email with the status, reason and events of every recipient.
 *
 * @property-read string                          $id
 * @property-read string                          $status
 * @property-read int                             $keyId
 * @property-read EmailSender                     $from
 * @property-read string                          $subject
 * @property-read int|null                        $templateId
 * @property-read \DateTimeImmutable              $createdAt
 * @property-read \DateTimeImmutable|null         $sentAt
 * @property-read \DateTimeImmutable|null         $completedAt
 * @property-read list<EmailRecipientWithEvents>  $recipients
 */
final class Email extends ApiObject
{
    protected const NESTED = [
        'from' => EmailSender::class,
        'recipients' => [EmailRecipientWithEvents::class],
    ];
}
