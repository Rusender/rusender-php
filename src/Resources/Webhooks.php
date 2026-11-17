<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Page;
use RuSender\Types\Webhook;
use RuSender\Types\WebhookTestResult;

/**
 * Webhook subscriptions to email and campaign events.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type CreateWebhookParams array{name: string, url: string, triggers: list<string>, status?: 'enabled'|'disabled'}
 * @phpstan-type UpdateWebhookParams array{name?: string, url?: string, triggers?: list<string>, status?: 'enabled'|'disabled'}
 */
final class Webhooks extends AbstractResource
{
    /**
     * Lists webhooks.
     * Scope: `webhooks.read`.
     *
     * @param array{page?: int, limit?: int} $params
     * @param RequestOptions                 $options
     *
     * @return Page<Webhook>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/webhooks', $params), $options, Webhook::class);
    }

    /**
     * Creates a webhook: events from `triggers` (constants of {@see \RuSender\Types\WebhookTrigger}) are sent to
     * `url`, your handler.
     * Scope: `webhooks.write`.
     *
     * @param CreateWebhookParams $params
     * @param RequestOptions      $options
     */
    public function create(array $params, array $options = []): Webhook
    {
        return $this->call(Request::withBody('POST', '/v1/public/webhooks', $params), $options, Webhook::class);
    }

    /**
     * Gets a webhook.
     * Scope: `webhooks.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $webhookId, array $options = []): Webhook
    {
        return $this->call(Request::get(Path::build('/v1/public/webhooks/{}', $webhookId)), $options, Webhook::class);
    }

    /**
     * Updates a webhook; only the fields you pass change. `status => 'disabled'` pauses it.
     * Scope: `webhooks.write`.
     *
     * @param UpdateWebhookParams $params
     * @param RequestOptions      $options
     */
    public function update(int $webhookId, array $params, array $options = []): Webhook
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/webhooks/{}', $webhookId), $params), $options, Webhook::class);
    }

    /**
     * Deletes a webhook. Events stop being sent to its URL at once; to pause it, set `status => 'disabled'`.
     * Scope: `webhooks.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $webhookId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/webhooks/{}', $webhookId)), $options, DeleteResult::class);
    }

    /**
     * Sends a test event to the webhook URL to check your handler. Real data is not changed. ValidationException
     * with WEBHOOK_DISABLED if the webhook is disabled: enable it first. Not retried on failure: a retry would send
     * a second event.
     * Scope: `webhooks.write`.
     *
     * @param RequestOptions $options
     */
    public function test(int $webhookId, array $options = []): WebhookTestResult
    {
        $request = Request::withoutBody('POST', Path::build('/v1/public/webhooks/{}/test', $webhookId), idempotent: false);

        return $this->call($request, $options, WebhookTestResult::class);
    }
}
