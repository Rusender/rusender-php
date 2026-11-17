<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/**
 * An API response with a non-2xx HTTP status.
 *
 * @phpstan-type ErrorDetail array{reason: string, field?: string, value?: mixed}
 */
class ApiException extends RuSenderException
{
    /**
     * @param list<ErrorDetail>             $details
     * @param array<string, list<string>>   $headers
     */
    public function __construct(
        string $message,
        /** Machine-readable code from `error.code`, e.g. `CONTACT_NOT_FOUND`. */
        public readonly string $errorCode,
        public readonly int $statusCode,
        public readonly array $details = [],
        /** Server-side request identifier — include it when you contact support. */
        public readonly ?string $requestId = null,
        public readonly array $headers = [],
        /** Seconds from `Retry-After`, null when the header is absent. */
        public readonly ?float $retryAfter = null,
    ) {
        $text = \sprintf('%s (HTTP %d, %s)', $message, $statusCode, $errorCode);
        parent::__construct($requestId !== null ? $text . ', request ' . $requestId : $text, $statusCode);
    }

    /**
     * @param list<ErrorDetail>           $details
     * @param array<string, list<string>> $headers
     */
    public static function create(
        string $message,
        string $errorCode,
        int $statusCode,
        array $details = [],
        ?string $requestId = null,
        array $headers = [],
        ?float $retryAfter = null,
    ): self {
        $class = match ($statusCode) {
            400, 422 => ValidationException::class,
            401 => AuthenticationException::class,
            402 => PaymentRequiredException::class,
            403 => PermissionException::class,
            404 => NotFoundException::class,
            409 => ConflictException::class,
            429 => RateLimitException::class,
            default => self::class,
        };

        return new $class($message, $errorCode, $statusCode, $details, $requestId, $headers, $retryAfter);
    }
}
