<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A chunked sending plan.
 *
 * @property-read int          $totalContacts
 * @property-read int          $chunksCount
 * @property-read list<Chunk>  $chunks
 */
final class ChunksPreview extends ApiObject
{
    protected const NESTED = [
        'chunks' => [Chunk::class],
    ];
}
