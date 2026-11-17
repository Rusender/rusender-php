<?php

/*
 * Creates a campaign draft with an attachment and previews the chunked sending plan. Does not send anything:
 * starting the campaign (campaigns->schedule) sends real emails, so it is left commented out.
 *
 *     RUSENDER_API_KEY=rs_ck_… php examples/campaign.php <template id> <sender id> <list id>
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RuSender\Client;

[, $templateId, $senderId, $listId] = array_map('intval', ($argv ?? []) + [0, 0, 0, 0]);
$rusender = new Client();

$files = $rusender->files->upload([['fileName' => 'price.txt', 'content' => 'November prices']]);

$campaign = $rusender->campaigns->create([
    'name' => 'New price list',
    'subject' => 'November prices',
    'templateId' => $templateId,
    'senderId' => $senderId,
    'listIds' => [$listId],
    'attachments' => array_map(static fn($file): int => $file->id, $files),
]);
echo "draft {$campaign->id} {$campaign->status} {$campaign->dashboardUrl}\n";

$plan = $rusender->campaigns->previewChunks($campaign->id, ['initialChunkSize' => 500, 'increaseChunkSizePercent' => 50]);
foreach ($plan->chunks as $chunk) {
    echo "{$chunk->index}: {$chunk->recipientsCount} on ", $chunk->scheduledSendDate->format('Y-m-d'), "\n";
}

// Starts sending real emails, in the same chunks:
// $rusender->campaigns->schedule($campaign->id, ['chunkSettings' => ['initialChunkSize' => 500, 'increaseChunkSizePercent' => 50]]);
