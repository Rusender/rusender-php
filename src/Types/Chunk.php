<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * One chunk of a chunked sending plan.
 *
 * @property-read int                $index
 * @property-read int                $recipientsCount
 * @property-read \DateTimeImmutable $scheduledSendDate
 */
final class Chunk extends ApiObject {}
