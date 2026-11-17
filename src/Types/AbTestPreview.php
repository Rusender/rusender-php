<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * How an A/B test would split the audience.
 *
 * @property-read int            $totalContacts
 * @property-read int            $percent
 * @property-read int            $variantsCount
 * @property-read AudienceShare  $perVariant
 * @property-read AudienceShare  $winnerAudience
 * @property-read string|null    $warning
 * @property-read string|null    $error
 * @property-read int|null       $allowedMinPercent null when the audience is empty
 */
final class AbTestPreview extends ApiObject
{
    protected const NESTED = [
        'perVariant' => AudienceShare::class,
        'winnerAudience' => AudienceShare::class,
    ];
}
