<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** 429: rate limit exceeded; wait retryAfter seconds before retrying. */
final class RateLimitException extends ApiException {}
