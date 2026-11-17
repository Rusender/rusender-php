<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A transactional email the contact received.
 *
 * @property-read int                $id
 * @property-read string             $status
 * @property-read string|null        $recipient
 * @property-read string|null        $subject
 * @property-read string             $senderEmail
 * @property-read string             $type template or plain
 * @property-read \DateTimeImmutable $createdAt
 */
final class ApiSendingHistoryItem extends ApiObject {}
