<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A key for sending transactional emails.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string             $status enabled, disabled, banned or error
 * @property-read bool               $isThrottled
 * @property-read bool               $isDefault
 * @property-read SendingKeyDomain   $domain
 * @property-read string             $dashboardUrl
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 */
final class SendingKey extends ApiObject
{
    protected const NESTED = [
        'domain' => SendingKeyDomain::class,
    ];
}
