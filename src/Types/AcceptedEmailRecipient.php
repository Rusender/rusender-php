<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A recipient of a just accepted email.
 *
 * @property-read string            $id
 * @property-read string            $email
 * @property-read string|null       $name
 * @property-read string            $type to, cc or bcc
 * @property-read string            $status queued, sending, delivered, bounced, failed or suppressed
 * @property-read EmailReason|null  $reason
 */
final class AcceptedEmailRecipient extends ApiObject
{
    protected const NESTED = [
        'reason' => EmailReason::class,
    ];
}
