<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A sender address for campaigns.
 *
 * @property-read int                     $id
 * @property-read string                  $email
 * @property-read string                  $verified enabled when the address is verified
 * @property-read int|null                $domainId
 * @property-read \DateTimeImmutable      $createdAt
 * @property-read \DateTimeImmutable|null $verifiedAt
 * @property-read string                  $dashboardUrl
 */
final class Sender extends ApiObject {}
