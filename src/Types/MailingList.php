<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A contact list.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string             $status enabled or disabled
 * @property-read int                $contactsCount
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 * @property-read string             $dashboardUrl
 */
final class MailingList extends ApiObject {}
