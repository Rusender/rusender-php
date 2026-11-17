<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A campaign the contact received.
 *
 * @property-read int                $id
 * @property-read string             $status
 * @property-read string             $distributionName
 * @property-read \DateTimeImmutable $createdAt
 */
final class CampaignSendingHistoryItem extends ApiObject {}
