<?php

/*
 * Goes through all pages of a list and through all emails with the cursor.
 *
 *     RUSENDER_API_KEY=rs_ck_… php examples/pagination.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RuSender\Client;
use RuSender\Types\Contact;
use RuSender\Types\EmailSummary;

/** @return Generator<int, Contact> */
function allContacts(Client $rusender): Generator
{
    $page = 1;
    do {
        $result = $rusender->contacts->list(['status' => ['active'], 'page' => $page, 'limit' => 100]);
        yield from $result->items;
    } while ($page++ < $result->pagination->totalPages);
}

/** @return Generator<int, EmailSummary> */
function allEmails(Client $rusender): Generator
{
    $cursor = null;
    do {
        $result = $rusender->emails->list(['limit' => 100, 'cursor' => $cursor]);
        yield from $result->items;
        $cursor = $result->nextCursor;
    } while ($cursor !== null);
}

$rusender = new Client();
echo 'active contacts: ', iterator_count(allContacts($rusender)), "\n";
foreach (allEmails($rusender) as $email) {
    echo $email->createdAt->format(DATE_ATOM), " {$email->subject} {$email->status}\n";
}
