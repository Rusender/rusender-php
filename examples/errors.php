<?php

/*
 * Handles API errors: validation, not found, rate limit with a retry under the same idempotency key.
 *
 *     RUSENDER_API_KEY=rs_ck_… php examples/errors.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RuSender\Client;
use RuSender\Exceptions\ApiException;
use RuSender\Exceptions\NetworkException;
use RuSender\Exceptions\NotFoundException;
use RuSender\Exceptions\RateLimitException;
use RuSender\Exceptions\ValidationException;

$rusender = new Client();

try {
    $rusender->emails->send(['from' => 'no-reply@shop.example', 'to' => 'not an address', 'subject' => 'Hi']);
} catch (ValidationException $error) {
    echo "invalid request: {$error->errorCode} ", json_encode($error->details), "\n";
}

try {
    $rusender->lists->get(999_999_999);
} catch (NotFoundException $error) {
    echo "not found: {$error->errorCode}, request {$error->requestId}\n";
}

// 429 is not retried by the SDK: waiting is up to you. The same idempotency key keeps the retry safe.
$options = ['idempotency_key' => 'order-1234-created'];
for ($attempt = 1; $attempt <= 3; ++$attempt) {
    try {
        $email = $rusender->emails->send([
            'from' => 'no-reply@shop.example',
            'to' => 'client@example.com',
            'subject' => 'Order #1234',
            'text' => 'Thank you!',
        ], $options);
        echo "sent {$email->id}\n";
        break;
    } catch (RateLimitException $error) {
        usleep((int) (($error->retryAfter ?? 1.0) * 1_000_000));
    } catch (ApiException $error) {
        // any other API error: the request id helps support find the request
        echo "{$error->statusCode} {$error->errorCode}: {$error->getMessage()}\n";
        break;
    } catch (NetworkException $error) {
        // no response: DNS, connection, timeout (TimeoutException); the SDK has already retried
        echo "network: {$error->getMessage()}\n";
        break;
    }
}
