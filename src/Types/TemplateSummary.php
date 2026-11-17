<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A template in a list, without HTML.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read string|null        $alias
 * @property-read string             $type html or unlayer
 * @property-read string|null        $thumbnail
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 * @property-read string             $dashboardUrl
 */
final class TemplateSummary extends ApiObject {}
