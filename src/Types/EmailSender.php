<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The sender address of an email.
 *
 * @property-read string       $email
 * @property-read string|null  $name
 */
final class EmailSender extends ApiObject {}
