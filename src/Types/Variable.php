<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A list variable.
 *
 * @property-read int                $id
 * @property-read string             $key
 * @property-read string             $name
 * @property-read string             $type string or phone
 * @property-read string|null        $defaultValue
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 */
final class Variable extends ApiObject {}
