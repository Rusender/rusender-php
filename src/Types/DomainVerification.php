<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A domain after a DNS check.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string             $status
 * @property-read bool               $isVerified
 * @property-read DnsRecords         $dnsRecords
 * @property-read \DateTimeImmutable $checkedAt
 * @property-read string             $dashboardUrl
 * @property-read list<string>       $warnings SPF_MULTIPLE_RECORDS, DKIM_DUPLICATE_RECORDS
 * @property-read string|null        $suggestedSpfRecord a merged SPF record to use if the current one is not valid
 */
final class DomainVerification extends ApiObject
{
    protected const NESTED = [
        'dnsRecords' => DnsRecords::class,
    ];
}
