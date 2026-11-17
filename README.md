# RuSender PHP SDK

**Русский** · [English](https://github.com/rusender/rusender-php/blob/main/README-EN.md)

Официальная библиотека для [публичного API RuSender](https://rusender.ru/docs/) на PHP. PHP 8.1–8.5, HTTP через
Guzzle, типы для PHPStan и Psalm в PHPDoc.

> Разработка ведётся во внутреннем репозитории RuSender, GitHub — зеркало. Ошибки и пожелания — в Issues.

## Установка

```bash
composer require rusender/rusender-php
```

## Быстрый старт

Создайте API-ключ в кабинете: **Интеграции → API**. Ключ читается из `RUSENDER_API_KEY` или передаётся в
конструктор. Пробелы, перевод строки и кавычки вокруг ключа отбрасываются.

```php
$rusender = new \RuSender\Client(); // ключ из RUSENDER_API_KEY

$email = $rusender->emails->send([
    'from' => ['email' => 'no-reply@shop.ru', 'name' => 'Магазин'],
    'to' => 'client@example.ru',
    'subject' => 'Заказ оформлен',
    'html' => '<p>Спасибо за заказ!</p>',
]);

echo $email->id, ' ', $email->status; // 0199b2f4-…  queued
```

Параметры — массив с полями ровно как в API (`templateAlias`, `replyTo`, `keyId`). Создайте клиент один раз и
переиспользуйте его.

Отправка по шаблону:

```php
$rusender->emails->send([
    'from' => 'no-reply@shop.ru',
    'to' => [['email' => 'client@example.ru', 'name' => 'Анна']],
    'subject' => 'Заказ №1234',
    'templateAlias' => 'order-created',
    'variables' => ['orderId' => 1234, 'total' => '2 990 ₽'],
]);
```

Вложения — содержимое файла как есть в `content` или путь к файлу в `path`; в base64 SDK кодирует сам:

```php
$rusender->emails->send([
    'from' => 'no-reply@shop.ru',
    'to' => 'client@example.ru',
    'subject' => 'Счёт',
    'text' => 'Счёт во вложении',
    'attachments' => [
        ['fileName' => 'invoice.pdf', 'path' => '/var/invoices/1234.pdf'],
        ['fileName' => 'act.pdf', 'content' => $pdfBytes],
    ],
]);
```

Рассылка с вложениями: сначала загрузите файлы (до 20, всего до 5 МБ), затем укажите их `id`:

```php
$files = $rusender->files->upload([
    ['fileName' => 'price.pdf', 'path' => 'price.pdf'],
    ['fileName' => 'catalog.pdf', 'content' => $catalogBytes],
]);

$campaign = $rusender->campaigns->create([
    'name' => 'Новый прайс',
    'subject' => 'Прайс на ноябрь',
    'templateId' => 12,
    'senderId' => 3,
    'listIds' => [7],
    'attachments' => array_map(fn ($file) => $file->id, $files),
]);
```

Статус письма и события по получателям:

```php
foreach ($rusender->emails->get($email->id)->recipients as $recipient) {
    echo $recipient->email, ' ', $recipient->status, ' ', $recipient->reason?->code, "\n";
}
```

## Ресурсы

| Ресурс | Что умеет |
|---|---|
| `emails` | отправка, список писем и доставок, статус письма, события получателя |
| `contacts` | поиск, изменение, отписка и возврат подписки, статистика, удаление, выгрузка, история отправок |
| `lists` | списки контактов; `lists->contacts` — добавление, перенос, импорт, выгрузка; `lists->variables` — переменные |
| `campaigns` | рассылки: черновики, запуск, пауза и продолжение, A/B-тест, отправка частями, статистика, получатели |
| `folders` | папки рассылок |
| `templates` | шаблоны писем |
| `senders`, `domains` | адреса и домены отправителей, проверка DNS |
| `sendingKeys` | ключи отправки транзакционных писем и их статистика |
| `segments` | сегменты контактов и условия |
| `webhooks` | подписки на события: доставки, открытия, отписки, статусы рассылок; тестовое событие |
| `files` | загрузка вложений для рассылок, список файлов, скачивание |
| `me` | аккаунт и права текущего ключа |

Цепочки в SDK пока не входят.

## Ответы и типы

Ответ — объект только для чтения с полями как в API: `$email->status` или `$email['status']`. Поля описаны в
PHPDoc (`@property-read`), поэтому IDE подсказывает их, а PHPStan и Psalm проверяют обращения. Вложенные объекты —
тоже объекты (`$email->recipients[0]->status`), моменты времени — `\DateTimeImmutable`. Поле, которого SDK ещё не
знает, доступно как элемент массива — `$email['newField']`; `toArray()` возвращает всё.

Списки возвращают страницу: `$page->items`, `$page->pagination`. Страница перебирается в `foreach`.

## Ошибки

Любой ответ API не 2xx — исключение, наследник `ApiException` с полями `errorCode`, `statusCode`, `details`,
`requestId` и `retryAfter`. Указывайте `requestId` в обращениях в поддержку.

```php
use RuSender\Exceptions\RateLimitException;
use RuSender\Exceptions\ValidationException;

try {
    $rusender->emails->send($params);
} catch (ValidationException $e) {
    error_log($e->errorCode . ' ' . json_encode($e->details)); // [{"field":"to","reason":"…"}]
    throw $e;
} catch (RateLimitException $e) {
    error_log("Повторите через {$e->retryAfter} с");
    throw $e;
}
```

| Класс (`RuSender\Exceptions\…`) | Когда |
|---|---|
| `ValidationException` | 400, 422 — запрос не прошёл проверку, поля в `details` |
| `AuthenticationException` | 401 — ключ неверен или отозван |
| `PaymentRequiredException` | 402 — не хватает средств или лимита тарифа |
| `PermissionException` | 403 — у ключа нет нужного scope |
| `NotFoundException` | 404 |
| `ConflictException` | 409 — конфликт или `Idempotency-Key` повторён с другим телом |
| `RateLimitException` | 429, `retryAfter` — секунды до повтора |
| `ApiException` | остальные статусы |
| `NetworkException`, `TimeoutException` | ответ не получен |
| `UnexpectedResponseException` | ответ вне контракта API: HTML-страница прокси с кодом 200, список без `items` |

Все классы наследуют `RuSenderException`. Неверная настройка клиента — `\InvalidArgumentException`.

## Ответ целиком

Методы возвращают данные. Чтобы получить ещё `requestId`, лимит запросов и сам PSR-7 ответ, передайте
`ResponseInfo` в опциях:

```php
$info = new \RuSender\Http\ResponseInfo();
$list = $rusender->lists->get(7, ['response_info' => $info]);
echo $info->requestId, ' ', $info->rateLimit?->remaining;
```

`$info->raw` — весь JSON ответа, в том числе поля, которых SDK ещё не знает.

## Даты

Моменты времени в ответах — `createdAt`, `updatedAt`, `scheduledAt`, `sentAt` и другие — `\DateTimeImmutable`.
Календарные дни без времени (`date` в активности рассылки, `dateFrom`/`dateTo` в статистике ключа) остаются
строками. В параметрах даты передаются `\DateTimeInterface` или строкой ISO 8601:

```php
$rusender->campaigns->schedule($campaignId, [
    'scheduledStartDate' => new DateTimeImmutable('2026-12-01 09:00', new DateTimeZone('Europe/Moscow')),
]);
```

## Необязательные поля и `null`

Поле, которого нет в массиве, не отправляется. `null` отправляется — так очищают значение там, где API это
допускает; `[]` очищает список:

```php
$rusender->templates->update(12, ['name' => 'Заказ оформлен', 'alias' => null]); // удалить алиас
$rusender->segments->update(5, ['conditions' => []]);                           // удалить все условия
```

## Повторы и идемпотентность

SDK сам повторяет запрос при 502, 503, 504 и сетевых ошибках — по умолчанию 2 раза с экспоненциальной
задержкой. POST, PATCH и DELETE повторяются безопасно: SDK отправляет `Idempotency-Key`, и сервер не выполнит
запрос дважды. Свой ключ — например, номер заказа — передайте в опциях:

```php
$rusender->emails->send($params, ['idempotency_key' => "order-{$order->id}"]);
```

При превышении лимита запросов (429) SDK не ждёт и не повторяет запрос сам, а сразу бросает
`RateLimitException`. Через сколько секунд можно повторить — в `$e->retryAfter`: подождите это время или поставьте
задачу в очередь. При повторе передайте тот же `idempotency_key`.

## Пагинация

Списки возвращают одну страницу:

```php
$page = $rusender->contacts->list(['status' => ['active'], 'page' => 1, 'limit' => 100]);
echo count($page), ' из ', $page->pagination->totalItems;
```

Письма и доставки листаются курсором:

```php
$cursor = null;
do {
    $page = $rusender->emails->list(['limit' => 100, 'cursor' => $cursor]);
    foreach ($page as $email) { /* … */ }
    $cursor = $page->nextCursor;
} while ($cursor !== null);
```

## Выгрузки и файлы

`contacts->export`, `lists->contacts->export` и `files->download` возвращают PSR-7 `ResponseInterface` с потоковым
телом: прочитайте его `(string) $response->getBody()` или копируйте поток частями.

```php
$body = $rusender->contacts->export(['format' => 'csv'])->getBody();
$file = fopen('contacts.csv', 'wb');
while (!$body->eof()) {
    fwrite($file, $body->read(65536));
}
```

## Настройки клиента

```php
$rusender = new \RuSender\Client($apiKey, [
    'base_url' => 'https://api.rusender.ru/api',
    'timeout' => 30,        // секунды, на одну попытку
    'max_retries' => 2,
    'headers' => ['X-Team' => 'billing'],
    'http_client' => new \GuzzleHttp\Client(['proxy' => 'http://proxy:3128']),
]);
```

Опции отдельного запроса — последний аргумент любого метода: `idempotency_key`, `request_id` (уходит в
`X-Request-ID`), `timeout`, `max_retries`, `headers`, `response_info`.

Готовые к запуску примеры — в [`examples/`](examples): отправка, ошибки и повторы, рассылка с вложениями,
выгрузка, пагинация. Полное руководство — в [документации](https://rusender.ru/docs/).

## Лицензия

MIT
