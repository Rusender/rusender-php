<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A subscription to events.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string             $url
 * @property-read list<string>       $triggers values of WebhookTrigger, e.g. email.delivered
 * @property-read string             $status enabled or disabled
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 * @property-read string             $dashboardUrl
 */
final class Webhook extends ApiObject {}
