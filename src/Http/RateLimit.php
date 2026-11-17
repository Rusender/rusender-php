<?php

declare(strict_types=1);

namespace RuSender\Http;

/** Rate limit of the API key from the X-RateLimit-* headers of a response. */
final class RateLimit
{
    public function __construct(
        /** Requests allowed in the current window. */
        public readonly int $limit,
        /** Requests left in the current window. */
        public readonly int $remaining,
        /** When the window resets, from X-RateLimit-Reset (Unix seconds in the header). */
        public readonly \DateTimeImmutable $resetAt,
    ) {}
}
