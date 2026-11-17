<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A campaign folder.
 *
 * @property-read int                $id
 * @property-read string             $name
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 */
final class Folder extends ApiObject {}
