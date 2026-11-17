<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of lists->contacts->create().
 *
 * @property-read int                              $id
 * @property-read string                           $email
 * @property-read string|null                      $name
 * @property-read string|null                      $phone
 * @property-read string                           $status
 * @property-read list<int>                        $listIds
 * @property-read list<ListRef>|null               $lists
 * @property-read array<string, string|null>|null  $variables
 * @property-read \DateTimeImmutable               $createdAt
 * @property-read \DateTimeImmutable               $updatedAt
 * @property-read \DateTimeImmutable|null          $lastActivityAt
 * @property-read string                           $dashboardUrl
 * @property-read bool                             $created true — the contact was created, false — an existing contact was reused
 */
final class ContactUpsertResult extends ApiObject
{
    protected const NESTED = [
        'lists' => [ListRef::class],
    ];
}
