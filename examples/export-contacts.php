<?php

/*
 * Exports active contacts of a list to a CSV file, streaming the response.
 *
 *     RUSENDER_API_KEY=rs_ck_… php examples/export-contacts.php <list id> contacts.csv
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RuSender\Client;

$listId = (int) ($argv[1] ?? 0);
$path = $argv[2] ?? 'contacts.csv';
$rusender = new Client();

$body = $rusender->lists->contacts->export($listId, ['format' => 'csv', 'status' => ['active']])->getBody();
$file = fopen($path, 'wb');
if ($file === false) {
    throw new RuntimeException("Cannot write {$path}");
}
while (!$body->eof()) {
    fwrite($file, $body->read(65536));
}
fclose($file);
$body->close();
echo "saved {$path}\n";
