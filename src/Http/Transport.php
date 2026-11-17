<?php

declare(strict_types=1);

namespace RuSender\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Message\ResponseInterface;
use RuSender\Exceptions\ApiException;
use RuSender\Exceptions\NetworkException;
use RuSender\Exceptions\RuSenderException;
use RuSender\Exceptions\TimeoutException;
use RuSender\Exceptions\UnexpectedResponseException;
use RuSender\Support\Dates;
use RuSender\Version;

/**
 * Sends requests: envelope, retries, idempotency, errors.
 *
 * @internal
 *
 * @phpstan-type RequestOptions array{
 *     idempotency_key?: string,
 *     request_id?: string,
 *     timeout?: int|float,
 *     max_retries?: int,
 *     headers?: array<string, string>,
 *     response_info?: ResponseInfo,
 * }
 * @phpstan-type Result array{data: mixed, meta: array<string, mixed>}
 */
final class Transport
{
    public const DEFAULT_BASE_URL = 'https://api.rusender.ru/api';
    public const DEFAULT_TIMEOUT = 30.0;
    public const DEFAULT_MAX_RETRIES = 2;

    private const MAX_RETRY_AFTER = 60.0;
    private const BASE_RETRY_DELAY = 0.5;
    private const MAX_RETRY_DELAY = 8.0;
    /** cURL error 28: operation timed out. */
    private const CURLE_OPERATION_TIMEDOUT = 28;
    private const TIMEOUT_EXCEPTIONS = [
        'GuzzleHttp\\Exception\\ConnectTimeoutException',
        'GuzzleHttp\\Exception\\NetworkTimeoutException',
        'GuzzleHttp\\Exception\\ResponseTimeoutException',
    ];

    private readonly string $baseUrl;
    private readonly string $userAgent;

    /** @var \Closure(float): void waits between attempts; tests replace it to record the delays */
    public \Closure $sleep;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $apiKey,
        private readonly ClientInterface $http,
        string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly array $headers,
    ) {
        self::validateSettings($timeout, $maxRetries);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->userAgent = \sprintf('rusender-php/%s (php/%s)', Version::VERSION, \PHP_VERSION);
        $this->sleep = static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };
    }

    /** The key must not reach logs through var_dump() or print_r() of the client. */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl, 'timeout' => $this->timeout, 'maxRetries' => $this->maxRetries];
    }

    public static function validateSettings(mixed $timeout, mixed $maxRetries): void
    {
        if (!(\is_int($timeout) || \is_float($timeout)) || !($timeout > 0) || !is_finite((float) $timeout)) {
            throw new \InvalidArgumentException(\sprintf('timeout must be a positive number of seconds, got %s', var_export($timeout, true)));
        }
        if (!\is_int($maxRetries) || $maxRetries < 0) {
            throw new \InvalidArgumentException(\sprintf('max_retries must be a non-negative integer, got %s', var_export($maxRetries, true)));
        }
    }

    /**
     * Sends a request whose response is the JSON envelope.
     *
     * @param RequestOptions $options
     *
     * @return Result
     */
    public function request(Request $request, array $options = []): array
    {
        $response = $this->send($request, $options, false);
        $body = (string) $response->getBody();
        $parsed = self::parseJson($body);
        // A 2xx that is not our envelope (an HTML page of a proxy, a captive portal) is not data
        if (!self::isEnvelope($parsed) || $parsed['ok'] !== true) {
            throw new UnexpectedResponseException(\sprintf(
                'Unexpected response from RuSender API (HTTP %d): %s',
                $response->getStatusCode(),
                self::describe($parsed, $body),
            ));
        }
        $meta = isset($parsed['meta']) && \is_array($parsed['meta']) ? $parsed['meta'] : [];
        /** @var array<string, mixed> $meta */
        if (isset($options['response_info'])) {
            $info = $options['response_info'];
            $requestId = $meta['requestId'] ?? null;
            $info->requestId = \is_string($requestId) ? $requestId : ($response->getHeaderLine('X-Request-ID') ?: null);
            $info->rateLimit = self::rateLimit($response);
            $info->response = $response;
            $info->raw = $parsed;
        }

        return ['data' => Dates::revive($parsed['data'] ?? null), 'meta' => $meta];
    }

    /**
     * Sends a request whose response is a file or a stream (exports, downloads): the body is not read.
     *
     * @param RequestOptions $options
     */
    public function requestRaw(Request $request, array $options = []): ResponseInterface
    {
        return $this->send($request, $options, true);
    }

    /**
     * @param RequestOptions $options
     */
    private function send(Request $request, array $options, bool $stream): ResponseInterface
    {
        $mutating = $request->method !== 'GET';
        $key = null;
        if ($mutating && $request->idempotent) {
            $key = $options['idempotency_key'] ?? self::uuid();
        }
        $safeToRetry = !$mutating || $key !== null;
        $timeout = $options['timeout'] ?? $this->timeout;
        $maxRetries = $options['max_retries'] ?? $this->maxRetries;
        self::validateSettings($timeout, $maxRetries);

        $guzzleOptions = [
            'http_errors' => false,
            'timeout' => (float) $timeout,
            'connect_timeout' => (float) $timeout,
            'stream' => $stream,
        ];

        for ($attempt = 0; ; ++$attempt) {
            $canRetry = $attempt < $maxRetries;
            try {
                // a new request per attempt: the body stream of a sent one is already read to the end
                $response = $this->http->send($this->build($request, $options, $key), $guzzleOptions);
            } catch (GuzzleException $error) {
                if ($canRetry && $safeToRetry) {
                    ($this->sleep)(self::backoff($attempt));
                    continue;
                }
                throw self::failure($error, (float) $timeout);
            }

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return $response;
            }
            $error = self::apiError($response);
            $delay = self::retryDelay($error, $safeToRetry, $canRetry, $attempt);
            if ($delay === null) {
                throw $error;
            }
            ($this->sleep)($delay);
        }
    }

    /**
     * @param RequestOptions $options
     */
    private function build(Request $request, array $options, ?string $key): Psr7Request
    {
        $url = $this->baseUrl . $request->path;
        $query = self::encodeQuery($request->query ?? []);
        if ($query !== '') {
            $url .= '?' . $query;
        }
        $headers = $this->headers;
        $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        $headers['Accept'] = 'application/json';
        $headers['User-Agent'] = $this->userAgent;
        if ($key !== null) {
            $headers['Idempotency-Key'] = $key;
        }
        if (isset($options['request_id'])) {
            $headers['X-Request-ID'] = $options['request_id'];
        }
        $body = null;
        if ($request->multipart !== null) {
            $multipart = new MultipartStream($request->multipart);
            $headers['Content-Type'] = 'multipart/form-data; boundary=' . $multipart->getBoundary();
            $body = $multipart;
        } elseif ($request->hasBody) {
            $headers['Content-Type'] = 'application/json';
            $body = self::encodeBody($request->body);
        }
        foreach ($options['headers'] ?? [] as $name => $value) {
            $headers[$name] = $value;
        }

        return new Psr7Request($request->method, $url, $headers, $body);
    }

    /**
     * Query parameters: null is left out, a list repeats the key, booleans are true/false, dates are ISO 8601.
     *
     * @param array<string, mixed> $query
     */
    public static function encodeQuery(array $query): string
    {
        $params = [];
        foreach ($query as $name => $value) {
            if ($value === null) {
                continue;
            }
            $params[$name] = \is_array($value) ? array_map(self::queryValue(...), array_values($value)) : self::queryValue($value);
        }

        return Query::build($params, \PHP_QUERY_RFC3986);
    }

    private static function queryValue(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => $value ? 'true' : 'false',
            $value instanceof \DateTimeInterface => Dates::iso($value),
            \is_scalar($value) => (string) $value,
            default => throw new \InvalidArgumentException('Query parameters must be scalars, dates or lists of them'),
        };
    }

    /** The body is sent as `{"data": body}`; no parameters is `{}`, not `[]`. */
    public static function encodeBody(mixed $body): string
    {
        return json_encode(
            ['data' => $body === [] ? new \stdClass() : self::prepare($body)],
            \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    private static function prepare(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return Dates::iso($value);
        }
        if (\is_array($value)) {
            return array_map(self::prepare(...), $value);
        }

        return $value;
    }

    private static function parseJson(string $body): mixed
    {
        if (trim($body) === '') {
            return null;
        }
        try {
            return json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $body;
        }
    }

    /** @phpstan-assert-if-true array<string, mixed> $value */
    private static function isEnvelope(mixed $value): bool
    {
        return \is_array($value) && isset($value['ok']) && \is_bool($value['ok']);
    }

    private static function describe(mixed $parsed, string $body): string
    {
        if ($parsed === null) {
            return 'empty body';
        }

        return \is_string($parsed) ? mb_substr($body, 0, 200) : 'JSON without the {ok, data} envelope';
    }

    private static function apiError(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $parsed = self::parseJson($body);
        $envelope = self::isEnvelope($parsed) ? $parsed : null;
        $payload = $envelope !== null && \is_array($envelope['error'] ?? null) ? $envelope['error'] : [];
        $meta = $envelope !== null && \is_array($envelope['meta'] ?? null) ? $envelope['meta'] : [];

        $code = \is_string($payload['code'] ?? null) ? $payload['code'] : 'HTTP_' . $status;
        $message = $payload['message'] ?? null;
        if (!\is_string($message)) {
            $message = 'RuSender API responded with HTTP ' . $status;
            if ($envelope === null) {
                // the message of a JSON error that is not our envelope, e.g. NestJS {"message": "Cannot POST /…"}
                $plain = \is_array($parsed) ? ($parsed['message'] ?? null) : null;
                if (\is_string($plain)) {
                    $message .= ': ' . $plain;
                } elseif (\is_array($plain)) {
                    $message .= ': ' . implode('; ', array_map(static fn(mixed $part): string => \is_scalar($part) ? (string) $part : '', $plain));
                } elseif (\is_string($parsed)) {
                    $message .= ': ' . mb_substr($body, 0, 200);
                }
            }
        }
        $details = [];
        foreach (\is_array($payload['details'] ?? null) ? $payload['details'] : [] as $detail) {
            if (\is_array($detail) && \is_string($detail['reason'] ?? null)) {
                /** @var array{reason: string, field?: string, value?: mixed} $detail */
                $details[] = $detail;
            }
        }
        $requestId = \is_string($meta['requestId'] ?? null) ? $meta['requestId'] : ($response->getHeaderLine('X-Request-ID') ?: null);

        /** @var array<string, list<string>> $headers */
        $headers = $response->getHeaders();

        return ApiException::create(
            $message,
            $code,
            $status,
            $details,
            $requestId,
            $headers,
            self::retryAfter($response),
        );
    }

    /** Seconds to wait before the next attempt, null — do not retry. */
    private static function retryDelay(ApiException $error, bool $safeToRetry, bool $canRetry, int $attempt): ?float
    {
        if (!$canRetry || !$safeToRetry) {
            return null;
        }
        // 502–504 and 409 REQUEST_ALREADY_IN_PROGRESS; 429 is not retried: whether to wait is up to the caller
        $retryable = $error->statusCode === 409
            ? $error->errorCode === 'REQUEST_ALREADY_IN_PROGRESS'
            : \in_array($error->statusCode, [502, 503, 504], true);
        if (!$retryable) {
            return null;
        }
        // The server says how long to wait; a longer wait than a minute is not ours to make
        if ($error->retryAfter !== null) {
            return $error->retryAfter <= self::MAX_RETRY_AFTER ? $error->retryAfter : null;
        }

        return self::backoff($attempt);
    }

    private static function retryAfter(ResponseInterface $response): ?float
    {
        $value = $response->getHeaderLine('Retry-After');
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return max(0.0, (float) $value);
        }
        $moment = strtotime($value);

        return $moment === false ? null : max(0.0, (float) ($moment - time()));
    }

    private static function rateLimit(ResponseInterface $response): ?RateLimit
    {
        $limit = $response->getHeaderLine('X-RateLimit-Limit');
        $remaining = $response->getHeaderLine('X-RateLimit-Remaining');
        $reset = $response->getHeaderLine('X-RateLimit-Reset');
        if (!ctype_digit($limit) || !ctype_digit($remaining) || !ctype_digit($reset)) {
            return null;
        }

        return new RateLimit((int) $limit, (int) $remaining, (new \DateTimeImmutable('@' . $reset)));
    }

    private static function backoff(int $attempt): float
    {
        $delay = min(self::MAX_RETRY_DELAY, self::BASE_RETRY_DELAY * 2 ** min($attempt, 10));

        return $delay * (0.75 + mt_rand() / mt_getrandmax() * 0.5);
    }

    private static function failure(GuzzleException $error, float $timeout): RuSenderException
    {
        if (self::isTimeout($error)) {
            return new TimeoutException(\sprintf('Request timed out after %s s', $timeout), 0, $error);
        }

        return new NetworkException('Request to RuSender API failed: ' . $error->getMessage(), 0, $error);
    }

    /** Guzzle 8 has timeout exception classes; Guzzle 7 reports the cURL error number in the handler context. */
    private static function isTimeout(GuzzleException $error): bool
    {
        foreach (self::TIMEOUT_EXCEPTIONS as $class) {
            if (is_a($error, $class)) {
                return true;
            }
        }
        if (method_exists($error, 'getHandlerContext')) {
            $context = $error->getHandlerContext();
            if (\is_array($context) && ($context['errno'] ?? null) === self::CURLE_OPERATION_TIMEDOUT) {
                return true;
            }
        }

        return stripos($error->getMessage(), 'timed out') !== false;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return \sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
