<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;

/**
 * The account and scopes of the current API key.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Me extends AbstractResource
{
    /**
     * Gets the account and the scopes of the current API key. Handy to check the key at startup.
     * No specific scope is required.
     *
     * @param RequestOptions $options
     */
    public function get(array $options = []): \RuSender\Types\Me
    {
        return $this->call(Request::get('/v1/public/me'), $options, \RuSender\Types\Me::class);
    }
}
