<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of lists->contacts->import().
 *
 * @property-read int                        $processed
 * @property-read int                        $addedCount
 * @property-read int                        $updatedCount
 * @property-read int                        $duplicateCount
 * @property-read int                        $errorCount
 * @property-read list<ImportRowError>|null  $errorsSample
 */
final class ImportContactsResult extends ApiObject
{
    protected const NESTED = [
        'errorsSample' => [ImportRowError::class],
    ];
}
