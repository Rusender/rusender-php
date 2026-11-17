<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The account and the scopes of the current API key.
 *
 * @property-read int           $accountId
 * @property-read string        $accountEmail
 * @property-read list<string>  $scopes scopes of the current API key, e.g. email.send, contacts.read
 */
final class Me extends ApiObject {}
