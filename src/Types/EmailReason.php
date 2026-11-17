<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Why a recipient has its status.
 *
 * @property-read string       $code e.g. RECIPIENT_UNSUBSCRIBED, DELIVERY_MAILBOX_NOT_FOUND
 * @property-read string|null  $bounceType permanent, transient or undetermined
 * @property-read string       $message
 * @property-read string|null  $smtpResponse
 */
final class EmailReason extends ApiObject {}
