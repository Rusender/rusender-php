<?php

/*
 * Sends a transactional email: plain HTML, by template, with an attachment.
 *
 *     RUSENDER_API_KEY=rs_ck_… php examples/send-email.php you@example.com
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RuSender\Client;

$to = $argv[1] ?? 'client@example.com';
$rusender = new Client(); // the key from RUSENDER_API_KEY
$from = ['email' => 'no-reply@shop.example', 'name' => 'Shop'];

$email = $rusender->emails->send([
    'from' => $from,
    'to' => $to,
    'subject' => 'Order placed',
    'html' => '<p>Thank you for your order!</p>',
    'text' => 'Thank you for your order!',
]);
echo "sent {$email->id} {$email->status}\n";

// A template with variables; the idempotency key makes a retry safe: the email is not sent twice
$rusender->emails->send([
    'from' => $from,
    'to' => [['email' => $to, 'name' => 'Anna']],
    'subject' => 'Order #1234',
    'templateAlias' => 'order-created',
    'variables' => ['orderId' => 1234, 'total' => '2 990 ₽'],
], ['idempotency_key' => 'order-1234-created']);

// Raw bytes in `content` (or a file `path`) — the SDK encodes them to base64
$rusender->emails->send([
    'from' => $from,
    'to' => $to,
    'subject' => 'Invoice',
    'text' => 'The invoice is attached',
    'attachments' => [['fileName' => 'invoice.txt', 'content' => 'Invoice #1234: 2 990 RUB']],
]);

// Delivery status of every recipient
foreach ($rusender->emails->get($email->id)->recipients as $recipient) {
    echo "{$recipient->email} {$recipient->status} ", $recipient->reason->code ?? '-', "\n";
}
