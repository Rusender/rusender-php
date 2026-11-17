<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A recipient of a campaign.
 *
 * @property-read int                     $contactId
 * @property-read string                  $email
 * @property-read string                  $status delivered, opened, clicked, unsubscribed, complaint, error, soft_bounced, hard_bounced, error_infrastructure or not_delivered
 * @property-read \DateTimeImmutable|null $sentAt
 * @property-read string|null             $responseSmtp
 */
final class CampaignRecipient extends ApiObject {}
