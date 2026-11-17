<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of emails->send(): the email is accepted for delivery.
 *
 * @property-read string                        $id
 * @property-read string                        $status queued, sending, delivered, bounced or failed
 * @property-read \DateTimeImmutable            $createdAt
 * @property-read list<AcceptedEmailRecipient>  $recipients
 */
final class AcceptedEmail extends ApiObject
{
    protected const NESTED = [
        'recipients' => [AcceptedEmailRecipient::class],
    ];
}
