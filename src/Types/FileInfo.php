<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An account file.
 *
 * @property-read int                $id
 * @property-read string             $fileName
 * @property-read int                $size size in bytes
 * @property-read \DateTimeImmutable $createdAt
 */
final class FileInfo extends ApiObject {}
