<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A sending domain.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string             $status enabled or disabled
 * @property-read bool               $isVerified
 * @property-read DnsRecords         $dnsRecords
 * @property-read \DateTimeImmutable $checkedAt
 * @property-read string             $dashboardUrl
 */
final class Domain extends ApiObject
{
    protected const NESTED = [
        'dnsRecords' => DnsRecords::class,
    ];
}
