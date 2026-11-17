# Changelog

**Русский** · [English](https://github.com/rusender/rusender-php/blob/main/CHANGELOG-EN.md)

## 0.1.0

Первая версия.

- Клиент `RuSender\Client` для публичного API: `emails`, `contacts`, `lists` (`contacts`, `variables`), `campaigns`,
  `folders`, `templates`, `senders`, `domains`, `sendingKeys`, `segments`, `webhooks`, `files`, `me`.
- Параметры — массивы с полями API, форма описана `@phpstan-type`; ответы — объекты только для чтения с
  `@property-read` и доступом как к массиву, вложенные объекты — тоже объекты.
- Исключения по HTTP-статусу с `errorCode`, `details`, `requestId`.
- Повторы при 502–504 и сетевых ошибках; автоматический `Idempotency-Key`. 429 не повторяется —
  `RateLimitException` с `retryAfter`.
- Константы событий вебхуков `RuSender\Types\WebhookTrigger` — значения для `triggers`.
- Загрузка вложений `files->upload` для рассылок; вложения писем — содержимое или путь к файлу.
- `response_info` у любого метода: `requestId`, лимит запросов, PSR-7 ответ и весь JSON ответа.
- Ответ вне контракта API (не JSON-конверт, список без `items`) — `UnexpectedResponseException`, а не пустые данные.
- Моменты времени в ответах — `\DateTimeImmutable`; в параметрах даты принимаются `\DateTimeInterface` или строкой.
- Пробелы, перевод строки и кавычки вокруг API-ключа отбрасываются.
- PHP 8.1–8.5, Guzzle 7 или 8.
