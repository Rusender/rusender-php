<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The records that verify a domain.
 *
 * @property-read DnsRecord  $dkim
 * @property-read DnsRecord  $spf
 * @property-read DnsRecord  $dmarc
 */
final class DnsRecords extends ApiObject
{
    protected const NESTED = [
        'dkim' => DnsRecord::class,
        'spf' => DnsRecord::class,
        'dmarc' => DnsRecord::class,
    ];
}
