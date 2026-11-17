<?php

declare(strict_types=1);

namespace RuSender\Http;

/**
 * One API operation.
 *
 * @internal
 */
final class Request
{
    /**
     * @param array<string, mixed>|null                                       $query
     * @param list<array{name: string, contents: string, filename: string}>|null $multipart a multipart/form-data body instead of JSON
     * @param bool $idempotent whether the operation supports Idempotency-Key: a mutating operation without it is
     *                         neither sent with the key nor retried, since a retry could perform the action twice
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly ?array $query = null,
        public readonly bool $hasBody = false,
        public readonly mixed $body = null,
        public readonly ?array $multipart = null,
        public readonly bool $idempotent = true,
    ) {}

    /** @param array<string, mixed>|null $query */
    public static function get(string $path, ?array $query = null): self
    {
        return new self('GET', $path, $query);
    }

    /** A request with a JSON body, sent as `{"data": body}`. */
    public static function withBody(string $method, string $path, mixed $body, bool $idempotent = true): self
    {
        return new self($method, $path, null, true, $body, null, $idempotent);
    }

    public static function withoutBody(string $method, string $path, bool $idempotent = true): self
    {
        return new self($method, $path, null, false, null, null, $idempotent);
    }
}
