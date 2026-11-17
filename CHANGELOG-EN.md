# Changelog

[Русский](https://github.com/rusender/rusender-php/blob/main/CHANGELOG.md) · **English**

## 0.1.0

First release.

- `RuSender\Client` for the public API: `emails`, `contacts`, `lists` (`contacts`, `variables`), `campaigns`,
  `folders`, `templates`, `senders`, `domains`, `sendingKeys`, `segments`, `webhooks`, `files`, `me`.
- Parameters are arrays with the API fields, their shape described with `@phpstan-type`; responses are read-only
  objects with `@property-read` and array access, nested objects are objects too.
- Exceptions by HTTP status with `errorCode`, `details`, `requestId`.
- Retries on 502–504 and network errors; automatic `Idempotency-Key`. 429 is not retried — `RateLimitException`
  with `retryAfter`.
- Webhook event constants `RuSender\Types\WebhookTrigger` — values for `triggers`.
- `files->upload` for campaign attachments; email attachments as contents or a file path.
- `response_info` on any method: `requestId`, rate limit, the PSR-7 response and the whole JSON of the response.
- A response outside the API contract (not the JSON envelope, a list without `items`) is
  `UnexpectedResponseException`, not empty data.
- Moments in time in responses are `\DateTimeImmutable`; date parameters take a `\DateTimeInterface` or a string.
- Whitespace, a trailing newline and quotes around the API key are trimmed.
- PHP 8.1–8.5, Guzzle 7 or 8.
