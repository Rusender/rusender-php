<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A contact.
 *
 * @property-read int                              $id
 * @property-read string                           $email
 * @property-read string|null                      $name
 * @property-read string|null                      $phone
 * @property-read string                           $status new, active, unsubscribed, unsubscribed_by_us, soft_bounced, hard_bounced, error or complaint
 * @property-read list<int>                        $listIds
 * @property-read list<ListRef>|null               $lists
 * @property-read array<string, string|null>|null  $variables values by variable key; null — not set
 * @property-read \DateTimeImmutable               $createdAt
 * @property-read \DateTimeImmutable               $updatedAt
 * @property-read \DateTimeImmutable|null          $lastActivityAt
 * @property-read string                           $dashboardUrl
 */
final class Contact extends ApiObject
{
    protected const NESTED = [
        'lists' => [ListRef::class],
    ];
}
