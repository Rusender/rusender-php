<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A DNS record to add to the domain zone.
 *
 * @property-read bool    $isVerified
 * @property-read string  $type
 * @property-read string  $host
 * @property-read string  $data
 */
final class DnsRecord extends ApiObject {}
