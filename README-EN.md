# RuSender PHP SDK

[Русский](https://github.com/rusender/rusender-php/blob/main/README.md) · **English**

The official PHP library for the [RuSender public API](https://rusender.ru/docs/). PHP 8.1–8.5, HTTP via Guzzle,
types for PHPStan and Psalm in PHPDoc.

> Development happens in RuSender's internal repository; GitHub is a mirror. Report bugs and ideas in Issues.

## Installation

```bash
composer require rusender/rusender-php
```

## Quick start

Create an API key in your account: **Integrations → API**. The key is read from `RUSENDER_API_KEY` or passed to
the constructor. Whitespace, a trailing newline and quotes around the key are trimmed.

```php
$rusender = new \RuSender\Client(); // the key from RUSENDER_API_KEY

$email = $rusender->emails->send([
    'from' => ['email' => 'no-reply@shop.com', 'name' => 'Shop'],
    'to' => 'client@example.com',
    'subject' => 'Order placed',
    'html' => '<p>Thank you for your order!</p>',
]);

echo $email->id, ' ', $email->status; // 0199b2f4-…  queued
```

Parameters are an array with fields exactly as in the API (`templateAlias`, `replyTo`, `keyId`). Create the client
once and reuse it.

Sending with a template:

```php
$rusender->emails->send([
    'from' => 'no-reply@shop.com',
    'to' => [['email' => 'client@example.com', 'name' => 'Anna']],
    'subject' => 'Order #1234',
    'templateAlias' => 'order-created',
    'variables' => ['orderId' => 1234, 'total' => '$29.90'],
]);
```

Attachments are the file contents as is in `content` or a file `path`; the SDK encodes them to base64:

```php
$rusender->emails->send([
    'from' => 'no-reply@shop.com',
    'to' => 'client@example.com',
    'subject' => 'Invoice',
    'text' => 'The invoice is attached',
    'attachments' => [
        ['fileName' => 'invoice.pdf', 'path' => '/var/invoices/1234.pdf'],
        ['fileName' => 'receipt.pdf', 'content' => $pdfBytes],
    ],
]);
```

A campaign with attachments: upload the files first (up to 20, 5 MB in total), then pass their `id`s:

```php
$files = $rusender->files->upload([
    ['fileName' => 'price.pdf', 'path' => 'price.pdf'],
    ['fileName' => 'catalog.pdf', 'content' => $catalogBytes],
]);

$campaign = $rusender->campaigns->create([
    'name' => 'New price list',
    'subject' => 'November prices',
    'templateId' => 12,
    'senderId' => 3,
    'listIds' => [7],
    'attachments' => array_map(fn ($file) => $file->id, $files),
]);
```

Email status and recipient events:

```php
foreach ($rusender->emails->get($email->id)->recipients as $recipient) {
    echo $recipient->email, ' ', $recipient->status, ' ', $recipient->reason?->code, "\n";
}
```

## Resources

| Resource | What it does |
|---|---|
| `emails` | sending, lists of emails and deliveries, email status, recipient events |
| `contacts` | search, update, unsubscribe and resubscribe, statistics, deletion, export, sending history |
| `lists` | contact lists; `lists->contacts` — add, move, import, export; `lists->variables` — variables |
| `campaigns` | campaigns: drafts, start, pause and resume, A/B tests, chunked sending, statistics, recipients |
| `folders` | campaign folders |
| `templates` | email templates |
| `senders`, `domains` | sender addresses and domains, DNS verification |
| `sendingKeys` | sending keys for transactional emails and their statistics |
| `segments` | contact segments and conditions |
| `webhooks` | event subscriptions: deliveries, opens, unsubscribes, campaign statuses; test event |
| `files` | campaign attachment upload, file list, download |
| `me` | the account and scopes of the current key |

Chains are not part of the SDK yet.

## Responses and types

A response is a read-only object with fields as in the API: `$email->status` or `$email['status']`. Fields are
described in PHPDoc (`@property-read`), so your IDE suggests them and PHPStan and Psalm check them. Nested objects
are objects too (`$email->recipients[0]->status`), moments in time are `\DateTimeImmutable`. A field the SDK does
not know yet is available as an array key — `$email['newField']`; `toArray()` returns everything.

Lists return a page: `$page->items`, `$page->pagination`. A page can be iterated with `foreach`.

## Errors

Any non-2xx API response throws a subclass of `ApiException` with `errorCode`, `statusCode`, `details`,
`requestId` and `retryAfter`. Include `requestId` when you contact support.

```php
use RuSender\Exceptions\RateLimitException;
use RuSender\Exceptions\ValidationException;

try {
    $rusender->emails->send($params);
} catch (ValidationException $e) {
    error_log($e->errorCode . ' ' . json_encode($e->details)); // [{"field":"to","reason":"…"}]
    throw $e;
} catch (RateLimitException $e) {
    error_log("Retry in {$e->retryAfter} s");
    throw $e;
}
```

| Class (`RuSender\Exceptions\…`) | When |
|---|---|
| `ValidationException` | 400, 422 — the request failed validation, fields are in `details` |
| `AuthenticationException` | 401 — the key is invalid or revoked |
| `PaymentRequiredException` | 402 — not enough balance or plan limit |
| `PermissionException` | 403 — the key lacks the required scope |
| `NotFoundException` | 404 |
| `ConflictException` | 409 — a conflict, or the `Idempotency-Key` was reused with a different body |
| `RateLimitException` | 429, `retryAfter` — seconds until a retry |
| `ApiException` | other statuses |
| `NetworkException`, `TimeoutException` | no response received |
| `UnexpectedResponseException` | a response outside the API contract: an HTML page of a proxy with status 200, a list without `items` |

All classes extend `RuSenderException`. An invalid client setting throws `\InvalidArgumentException`.

## The whole response

Methods return the data. To also get the `requestId`, the rate limit and the PSR-7 response itself, pass a
`ResponseInfo` in the options:

```php
$info = new \RuSender\Http\ResponseInfo();
$list = $rusender->lists->get(7, ['response_info' => $info]);
echo $info->requestId, ' ', $info->rateLimit?->remaining;
```

`$info->raw` is the whole JSON of the response, including fields the SDK does not know yet.

## Dates

Moments in time in responses — `createdAt`, `updatedAt`, `scheduledAt`, `sentAt` and others — are
`\DateTimeImmutable`. Calendar days without time (`date` in campaign activity, `dateFrom`/`dateTo` in key
statistics) stay strings. Date parameters take a `\DateTimeInterface` or an ISO 8601 string:

```php
$rusender->campaigns->schedule($campaignId, [
    'scheduledStartDate' => new DateTimeImmutable('2026-12-01 09:00', new DateTimeZone('Europe/Moscow')),
]);
```

## Optional fields and `null`

A field missing from the array is not sent. `null` is sent — that is how you clear a value where the API allows
it; `[]` clears a list:

```php
$rusender->templates->update(12, ['name' => 'Order placed', 'alias' => null]); // remove the alias
$rusender->segments->update(5, ['conditions' => []]);                         // remove all conditions
```

## Retries and idempotency

The SDK retries on 502, 503, 504 and network errors — 2 times by default, with exponential backoff. POST,
PATCH and DELETE are retried safely: the SDK sends an `Idempotency-Key`, so the server does not perform the
request twice. Pass your own key — an order number, for example — in the options:

```php
$rusender->emails->send($params, ['idempotency_key' => "order-{$order->id}"]);
```

When the rate limit is exceeded (429), the SDK neither waits nor retries; it throws `RateLimitException` right
away. How long to wait is in `$e->retryAfter`: wait that long or queue the job. Pass the same `idempotency_key`
when you retry.

## Pagination

Lists return one page:

```php
$page = $rusender->contacts->list(['status' => ['active'], 'page' => 1, 'limit' => 100]);
echo count($page), ' of ', $page->pagination->totalItems;
```

Emails and deliveries use a cursor:

```php
$cursor = null;
do {
    $page = $rusender->emails->list(['limit' => 100, 'cursor' => $cursor]);
    foreach ($page as $email) { /* … */ }
    $cursor = $page->nextCursor;
} while ($cursor !== null);
```

## Exports and files

`contacts->export`, `lists->contacts->export` and `files->download` return a PSR-7 `ResponseInterface` with a
streamed body: read it with `(string) $response->getBody()` or copy the stream in chunks.

```php
$body = $rusender->contacts->export(['format' => 'csv'])->getBody();
$file = fopen('contacts.csv', 'wb');
while (!$body->eof()) {
    fwrite($file, $body->read(65536));
}
```

## Client settings

```php
$rusender = new \RuSender\Client($apiKey, [
    'base_url' => 'https://api.rusender.ru/api',
    'timeout' => 30,        // seconds, per attempt
    'max_retries' => 2,
    'headers' => ['X-Team' => 'billing'],
    'http_client' => new \GuzzleHttp\Client(['proxy' => 'http://proxy:3128']),
]);
```

Per-request options are the last argument of any method: `idempotency_key`, `request_id` (sent as
`X-Request-ID`), `timeout`, `max_retries`, `headers`, `response_info`.

Runnable examples are in [`examples/`](examples): sending, errors and retries, a campaign with attachments, export,
pagination. The full guide is in the [documentation](https://rusender.ru/docs/).

## License

MIT
