<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\Page;
use RuSender\Types\ResendConfirmationResult;
use RuSender\Types\Sender;

/**
 * Sender addresses for campaigns.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Senders extends AbstractResource
{
    /**
     * Lists sender addresses, filterable by verification.
     * Scope: `senders.read`.
     *
     * @param array{page?: int, limit?: int, verified?: 'enabled'|'disabled'} $params
     * @param RequestOptions                                                   $options
     *
     * @return Page<Sender>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/senders', $params), $options, Sender::class);
    }

    /**
     * Adds a sender address. A confirmation email is sent to it; the sender is verified once the link in it is
     * clicked.
     * Scope: `senders.write`.
     *
     * @param array{email: string} $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): Sender
    {
        return $this->call(Request::withBody('POST', '/v1/public/senders', $params), $options, Sender::class);
    }

    /**
     * Gets a sender address.
     * Scope: `senders.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $senderId, array $options = []): Sender
    {
        return $this->call(Request::get(Path::build('/v1/public/senders/{}', $senderId)), $options, Sender::class);
    }

    /**
     * Sends the confirmation email to an unverified sender address again.
     * Scope: `senders.write`.
     *
     * @param RequestOptions $options
     */
    public function resendConfirmation(int $senderId, array $options = []): ResendConfirmationResult
    {
        $request = Request::withoutBody('POST', Path::build('/v1/public/senders/{}/resend-confirmation', $senderId));

        return $this->call($request, $options, ResendConfirmationResult::class);
    }
}
