<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Events a webhook subscribes to — values for `triggers` in `webhooks->create()` and `webhooks->update()`:
 * `['triggers' => [WebhookTrigger::EMAIL_DELIVERED]]`. `triggers` stays `list<string>`: the API may add events
 * before the SDK has a constant for them.
 */
final class WebhookTrigger
{
    // Emails sent through `emails->send()`
    public const EMAIL_SUPPRESSED = 'email.suppressed';
    public const EMAIL_DELIVERED = 'email.delivered';
    public const EMAIL_BOUNCED = 'email.bounced';
    public const EMAIL_FAILED = 'email.failed';
    public const EMAIL_OPENED = 'email.opened';
    public const EMAIL_CLICKED = 'email.clicked';
    public const EMAIL_UNSUBSCRIBED = 'email.unsubscribed';
    public const EMAIL_COMPLAINED = 'email.complained';

    // Campaigns
    public const MAIL_DISTRIBUTION_STARTED = 'mail_distribution.started';
    public const MAIL_DISTRIBUTION_COMPLETED = 'mail_distribution.completed';
    public const MAIL_DISTRIBUTION_BANNED = 'mail_distribution.banned';
    public const MAIL_DISTRIBUTION_MODERATING = 'mail_distribution.moderating';
    public const MAIL_DISTRIBUTION_APPROVED = 'mail_distribution.approved';
    public const MAIL_DISTRIBUTION_REJECTED = 'mail_distribution.rejected';

    // Campaign emails, per recipient
    public const DATA_MAIL_DISTRIBUTION_DELIVERED = 'data_mail_distribution.delivered';
    public const DATA_MAIL_DISTRIBUTION_OPEN = 'data_mail_distribution.open';
    public const DATA_MAIL_DISTRIBUTION_CLICK = 'data_mail_distribution.click';
    public const DATA_MAIL_DISTRIBUTION_HARD_BOUNCED = 'data_mail_distribution.hard_bounced';
    public const DATA_MAIL_DISTRIBUTION_SOFT_BOUNCED = 'data_mail_distribution.soft_bounced';
    public const DATA_MAIL_DISTRIBUTION_ERROR = 'data_mail_distribution.error';
    public const DATA_MAIL_DISTRIBUTION_COMPLAINT = 'data_mail_distribution.complaint';
    public const DATA_MAIL_DISTRIBUTION_UNSUBSCRIBE = 'data_mail_distribution.unsubscribe';

    // Transactional emails of the legacy route
    public const EXTERNAL_MAIL_DELIVERED = 'external_mail.delivered';
    public const EXTERNAL_MAIL_OPEN = 'external_mail.open';
    public const EXTERNAL_MAIL_CLICK = 'external_mail.click';
    public const EXTERNAL_MAIL_HARD_BOUNCED = 'external_mail.hard_bounced';
    public const EXTERNAL_MAIL_SOFT_BOUNCED = 'external_mail.soft_bounced';
    public const EXTERNAL_MAIL_ERROR = 'external_mail.error';
    public const EXTERNAL_MAIL_COMPLAINT = 'external_mail.complaint';
    public const EXTERNAL_MAIL_UNSUBSCRIBE = 'external_mail.unsubscribe';

    // Contacts
    public const CONTACT_ADDED = 'contact.added';
    public const CONTACT_REMOVED = 'contact.removed';

    // Sending keys
    public const SENDING_KEY_STATE_CHANGED = 'sending_key.state_changed';

    private function __construct() {}
}
