<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\AcceptedEmail;
use RuSender\Types\CursorPage;
use RuSender\Types\Delivery;
use RuSender\Types\Email;
use RuSender\Types\EmailRecipientDetails;
use RuSender\Types\EmailRecipientEvent;
use RuSender\Types\EmailSummary;
use RuSender\Types\Page;

/**
 * Transactional emails: sending, status, history and recipient events.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type Address array{email: string, name?: string}
 * @phpstan-type Recipient string|Address
 * @phpstan-type Attachment array{fileName: string, content: string}|array{fileName: string, path: string}|array{fileId: int}
 * @phpstan-type SendEmailParams array{
 *     from: Recipient,
 *     to: Recipient|list<Recipient>,
 *     subject: string,
 *     cc?: Recipient|list<Recipient>,
 *     bcc?: Recipient|list<Recipient>,
 *     replyTo?: Recipient|list<Recipient>,
 *     previewText?: string,
 *     html?: string,
 *     text?: string,
 *     templateId?: int,
 *     templateAlias?: string,
 *     variables?: array<string, string|int|float|bool|null>,
 *     headers?: array<string, string>,
 *     attachments?: list<Attachment>,
 *     keyId?: int,
 * }
 * @phpstan-type ListEmailsParams array{
 *     limit?: int,
 *     cursor?: string|null,
 *     keyId?: int,
 *     dateFrom?: \DateTimeInterface|string,
 *     dateTo?: \DateTimeInterface|string,
 *     from?: string,
 * }
 * @phpstan-type ListEmailRecipientsParams array{
 *     limit?: int,
 *     cursor?: string|null,
 *     keyId?: int,
 *     dateFrom?: \DateTimeInterface|string,
 *     dateTo?: \DateTimeInterface|string,
 *     from?: string,
 *     status?: list<string>,
 *     to?: string,
 * }
 * @phpstan-type ListRecipientEventsParams array{page?: int, limit?: int, type?: list<string>}
 */
final class Emails extends AbstractResource
{
    /**
     * Sends a transactional email. Up to 50 recipients in `to`, `cc` and `bcc` together, up to 5 in `replyTo`,
     * up to 20 attachments. An attachment is raw bytes in `content` or a file `path` — the SDK encodes it to
     * base64 — or `fileId` of a file uploaded with files->upload().
     * The email is accepted for delivery (status `queued`); track delivery with get() or webhooks.
     * Scope: `email.send`.
     *
     * @param SendEmailParams $params
     * @param RequestOptions  $options
     */
    public function send(array $params, array $options = []): AcceptedEmail
    {
        return $this->call(Request::withBody('POST', '/v1/public/emails', self::sendBody($params)), $options, AcceptedEmail::class);
    }

    /**
     * Lists sent emails, newest first, with cursor pagination: request the next page with `cursor` set to
     * `$page->nextCursor`. `limit` 1–100, 20 by default.
     * Scope: `email.read`.
     *
     * @param ListEmailsParams $params
     * @param RequestOptions   $options
     *
     * @return CursorPage<EmailSummary>
     */
    public function list(array $params = [], array $options = []): CursorPage
    {
        return $this->cursorPage(Request::get('/v1/public/emails', $params), $options, EmailSummary::class);
    }

    /**
     * Gets an email with the status and reason of every recipient.
     * Scope: `email.read`.
     *
     * @param RequestOptions $options
     */
    public function get(string $emailId, array $options = []): Email
    {
        return $this->call(Request::get(Path::build('/v1/public/emails/{}', $emailId)), $options, Email::class);
    }

    /**
     * Lists deliveries across all emails — one item per recipient, filterable by status and address.
     * Cursor pagination: request the next page with `cursor` set to `$page->nextCursor`.
     * Scope: `email.read`.
     *
     * @param ListEmailRecipientsParams $params
     * @param RequestOptions            $options
     *
     * @return CursorPage<Delivery>
     */
    public function listRecipients(array $params = [], array $options = []): CursorPage
    {
        return $this->cursorPage(Request::get('/v1/public/emails/recipients', $params), $options, Delivery::class);
    }

    /**
     * Gets one recipient of an email with its status, reason and events.
     * Scope: `email.read`.
     *
     * @param RequestOptions $options
     */
    public function getRecipient(string $emailId, string $recipientId, array $options = []): EmailRecipientDetails
    {
        $request = Request::get(Path::build('/v1/public/emails/{}/recipients/{}', $emailId, $recipientId));

        return $this->call($request, $options, EmailRecipientDetails::class);
    }

    /**
     * Lists events of an email recipient: delivery, bounces, opens, clicks, unsubscribes.
     * Scope: `email.read`.
     *
     * @param ListRecipientEventsParams $params
     * @param RequestOptions            $options
     *
     * @return Page<EmailRecipientEvent>
     */
    public function listRecipientEvents(string $emailId, string $recipientId, array $params = [], array $options = []): Page
    {
        $request = Request::get(Path::build('/v1/public/emails/{}/recipients/{}/events', $emailId, $recipientId), $params);

        return $this->page($request, $options, EmailRecipientEvent::class);
    }

    /**
     * Addresses as strings become {email}, a single recipient becomes a list, attachment bytes and files become
     * base64.
     *
     * @param SendEmailParams $params
     *
     * @return array<string, mixed>
     */
    private static function sendBody(array $params): array
    {
        $body = $params;
        $body['from'] = self::address($params['from']);
        foreach (['to', 'cc', 'bcc', 'replyTo'] as $field) {
            if (\array_key_exists($field, $params)) {
                $body[$field] = self::addresses($params[$field]);
            }
        }
        if (isset($params['attachments'])) {
            $body['attachments'] = array_map(self::attachment(...), $params['attachments']);
        }

        return $body;
    }

    /**
     * @param string|array{email: string, name?: string} $recipient
     *
     * @return array{email: string, name?: string}
     */
    private static function address(string|array $recipient): array
    {
        return \is_string($recipient) ? ['email' => $recipient] : $recipient;
    }

    /**
     * @param string|array{email: string, name?: string}|list<string|array{email: string, name?: string}> $value
     *
     * @return list<array{email: string, name?: string}>
     */
    private static function addresses(string|array $value): array
    {
        if (\is_string($value) || isset($value['email'])) {
            /** @var string|array{email: string, name?: string} $value */
            return [self::address($value)];
        }

        /** @var list<string|array{email: string, name?: string}> $value */
        return array_map(self::address(...), $value);
    }

    /**
     * @param array{fileName: string, content: string}|array{fileName: string, path: string}|array{fileId: int} $attachment
     *
     * @return array{fileName: string, content: string}|array{fileId: int}
     */
    private static function attachment(array $attachment): array
    {
        if (isset($attachment['fileId'])) {
            return ['fileId' => $attachment['fileId']];
        }
        if (isset($attachment['path'])) {
            $content = @file_get_contents($attachment['path']);
            if ($content === false) {
                throw new \InvalidArgumentException(\sprintf('Cannot read attachment %s', $attachment['path']));
            }
        } else {
            $content = $attachment['content'];
        }

        return ['fileName' => $attachment['fileName'], 'content' => base64_encode($content)];
    }
}
