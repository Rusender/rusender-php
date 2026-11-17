<?php

declare(strict_types=1);

namespace RuSender;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use RuSender\Http\Transport;
use RuSender\Resources\Campaigns;
use RuSender\Resources\Contacts;
use RuSender\Resources\Domains;
use RuSender\Resources\Emails;
use RuSender\Resources\Files;
use RuSender\Resources\Folders;
use RuSender\Resources\Lists;
use RuSender\Resources\Me;
use RuSender\Resources\Segments;
use RuSender\Resources\Senders;
use RuSender\Resources\SendingKeys;
use RuSender\Resources\Templates;
use RuSender\Resources\Webhooks;

/**
 * Client for the RuSender public API. Create it once and reuse it.
 *
 *     $rusender = new \RuSender\Client(); // the key from RUSENDER_API_KEY
 *     $email = $rusender->emails->send([
 *         'from' => 'no-reply@shop.ru',
 *         'to' => 'client@example.ru',
 *         'subject' => 'Hi',
 *         'text' => 'Hello',
 *     ]);
 *
 * @phpstan-type ClientOptions array{
 *     base_url?: string,
 *     timeout?: int|float,
 *     max_retries?: int,
 *     headers?: array<string, string>,
 *     http_client?: ClientInterface,
 * }
 */
final class Client
{
    /** Transactional emails: sending, status, history and recipient events. */
    public readonly Emails $emails;

    /** Contacts across all lists: search, update, subscription, export, sending history. */
    public readonly Contacts $contacts;

    /** Contact lists, their contacts (`lists->contacts`) and variables (`lists->variables`). */
    public readonly Lists $lists;

    /** Campaigns: drafts, start, pause, A/B tests, chunked sending, statistics. */
    public readonly Campaigns $campaigns;

    /** Campaign folders. */
    public readonly Folders $folders;

    /** Email templates. */
    public readonly Templates $templates;

    /** Sender addresses for campaigns. */
    public readonly Senders $senders;

    /** Sender domains and their DNS verification. */
    public readonly Domains $domains;

    /** Webhook subscriptions to email and campaign events. */
    public readonly Webhooks $webhooks;

    /** Contact segments built from conditions. */
    public readonly Segments $segments;

    /** Sending keys used for transactional emails. */
    public readonly SendingKeys $sendingKeys;

    /** Account files: campaign attachments. */
    public readonly Files $files;

    /** The account and scopes of the current API key. */
    public readonly Me $me;

    private readonly Transport $transport;

    /**
     * @param string|null   $apiKey  API key token (`rs_ck_…`). Read from RUSENDER_API_KEY when null or blank;
     *                               surrounding whitespace, a trailing newline and quotes are trimmed.
     * @param ClientOptions $options `timeout` — seconds per attempt, 30 by default; `max_retries` — retries on
     *                               502–504 and network errors, 2 by default (429 is not retried); `headers` — sent
     *                               with every request; `http_client` — your own Guzzle client (proxy, logging,
     *                               tests).
     *
     * @throws \InvalidArgumentException no API key, or an invalid timeout or max_retries
     */
    public function __construct(#[\SensitiveParameter] ?string $apiKey = null, array $options = [])
    {
        $key = self::normalizeKey($apiKey);
        if ($key === '') {
            $env = getenv('RUSENDER_API_KEY');
            $key = self::normalizeKey(\is_string($env) ? $env : null);
        }
        if ($key === '') {
            throw new \InvalidArgumentException('RuSender API key is missing. Pass it to new Client($apiKey) or set the RUSENDER_API_KEY environment variable.');
        }

        $this->transport = new Transport(
            $key,
            $options['http_client'] ?? new GuzzleClient(),
            $options['base_url'] ?? Transport::DEFAULT_BASE_URL,
            (float) ($options['timeout'] ?? Transport::DEFAULT_TIMEOUT),
            $options['max_retries'] ?? Transport::DEFAULT_MAX_RETRIES,
            $options['headers'] ?? [],
        );

        $this->emails = new Emails($this->transport);
        $this->contacts = new Contacts($this->transport);
        $this->lists = new Lists($this->transport);
        $this->campaigns = new Campaigns($this->transport);
        $this->folders = new Folders($this->transport);
        $this->templates = new Templates($this->transport);
        $this->senders = new Senders($this->transport);
        $this->domains = new Domains($this->transport);
        $this->webhooks = new Webhooks($this->transport);
        $this->segments = new Segments($this->transport);
        $this->sendingKeys = new SendingKeys($this->transport);
        $this->files = new Files($this->transport);
        $this->me = new Me($this->transport);
    }

    /** The key must not reach logs through var_dump() or print_r() of the client. */
    public function __debugInfo(): array
    {
        return ['transport' => $this->transport];
    }

    /** A key from a secret file or an env file often carries a trailing newline or quotes: the server would reject it. */
    private static function normalizeKey(?string $key): string
    {
        return trim(trim($key ?? ''), "\"'");
    }
}
