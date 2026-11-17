<?php

declare(strict_types=1);

namespace RuSender\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use RuSender\Client;
use RuSender\Exceptions\ApiException;
use RuSender\Exceptions\AuthenticationException;
use RuSender\Exceptions\ConflictException;
use RuSender\Exceptions\NetworkException;
use RuSender\Exceptions\NotFoundException;
use RuSender\Exceptions\PaymentRequiredException;
use RuSender\Exceptions\PermissionException;
use RuSender\Exceptions\RateLimitException;
use RuSender\Exceptions\TimeoutException;
use RuSender\Exceptions\UnexpectedResponseException;
use RuSender\Exceptions\ValidationException;
use RuSender\Http\ResponseInfo;
use RuSender\Tests\Support\TestCase;
use RuSender\Version;

final class ClientTest extends TestCase
{
    private const ME = ['accountId' => 1, 'accountEmail' => 'owner@shop.ru', 'scopes' => ['email.send']];

    protected function tearDown(): void
    {
        putenv('RUSENDER_API_KEY');
    }

    public function testMissingApiKey(): void
    {
        putenv('RUSENDER_API_KEY');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key is missing');
        new Client();
    }

    public function testBlankEnvironmentKeyIsMissing(): void
    {
        putenv("RUSENDER_API_KEY= \n");
        $this->expectException(\InvalidArgumentException::class);
        new Client('  ');
    }

    /** @return iterable<string, array{string}> */
    public static function untrimmedKeys(): iterable
    {
        yield 'newline' => [self::API_KEY . "\n"];
        yield 'spaces' => ['  ' . self::API_KEY . ' '];
        yield 'double quotes' => ['"' . self::API_KEY . '"'];
        yield 'single quotes' => ["'" . self::API_KEY . "'"];
    }

    #[DataProvider('untrimmedKeys')]
    public function testApiKeyIsTrimmed(string $key): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([self::ok(self::ME)]));
        $stack->push(Middleware::history($history));
        (new Client($key, ['http_client' => new GuzzleClient(['handler' => $stack])]))->me->get();
        self::assertSame('Bearer ' . self::API_KEY, $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function testApiKeyFromEnvironment(): void
    {
        putenv("RUSENDER_API_KEY=rs_ck_v1_from_env\n");
        $history = [];
        $stack = HandlerStack::create(new MockHandler([self::ok(self::ME)]));
        $stack->push(Middleware::history($history));
        (new Client('', ['http_client' => new GuzzleClient(['handler' => $stack])]))->me->get();
        self::assertSame('Bearer rs_ck_v1_from_env', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function testDefaultHeadersAndBaseUrl(): void
    {
        $client = $this->client([self::ok(self::ME)]);
        $me = $client->me->get();
        self::assertSame(['email.send'], $me->scopes);
        $request = $this->request();
        self::assertSame('https://api.rusender.ru/api/v1/public/me', (string) $request->getUri());
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertMatchesRegularExpression('#^rusender-php/\d+\.\d+\.\d+\S* \(php/\d+\.\d+\.\d+\S*\)$#', $request->getHeaderLine('User-Agent'));
        self::assertFalse($request->hasHeader('Idempotency-Key'));
        self::assertFalse($request->hasHeader('Content-Type'));
    }

    public function testSettingsHeadersAndRequestId(): void
    {
        $client = $this->client([self::ok(self::ME)], ['base_url' => 'http://localhost:3000/api/', 'headers' => ['X-Team' => 'billing']]);
        $client->me->get(['request_id' => 'trace-1', 'headers' => ['X-Debug' => '1']]);
        $request = $this->request();
        self::assertSame('http://localhost:3000/api/v1/public/me', (string) $request->getUri());
        self::assertSame('billing', $request->getHeaderLine('X-Team'));
        self::assertSame('1', $request->getHeaderLine('X-Debug'));
        self::assertSame('trace-1', $request->getHeaderLine('X-Request-ID'));
    }

    public function testQueryArraysRepeatTheKeyAndNullIsLeftOut(): void
    {
        $client = $this->client([self::ok(['items' => []], self::PAGINATION)]);
        $client->contacts->list(['status' => ['active', 'new'], 'page' => 2, 'email' => 'a+b@example.ru']);
        self::assertSame('status=active&status=new&page=2&email=a%2Bb%40example.ru', $this->request()->getUri()->getQuery());
    }

    public function testQueryDatesAndBooleans(): void
    {
        $client = $this->client([self::ok(['items' => [], 'hasMore' => false, 'nextCursor' => null]), self::ok(['items' => []], self::PAGINATION)]);
        $client->emails->list(['dateFrom' => new \DateTimeImmutable('2026-11-01T06:00:00+03:00')]);
        $client->campaigns->list(['withStats' => true]);
        self::assertSame('dateFrom=2026-11-01T03%3A00%3A00.000Z', $this->request(0)->getUri()->getQuery());
        self::assertSame('withStats=true', $this->request(1)->getUri()->getQuery());
    }

    public function testPathParametersAreEncoded(): void
    {
        $client = $this->client([self::ok(['id' => 'x'])]);
        $client->emails->get('a/b?c');
        self::assertSame('/api/v1/public/emails/a%2Fb%3Fc', $this->request()->getUri()->getPath());
    }

    public function testVersionIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/', Version::VERSION);
    }

    public function testApiKeyDoesNotLeak(): void
    {
        $client = $this->client();
        foreach ([$client, $client->emails, $client->lists->contacts] as $value) {
            ob_start();
            var_dump($value);
            $dump = (string) ob_get_clean();
            self::assertStringNotContainsString(self::API_KEY, $dump);
            self::assertStringNotContainsString(self::API_KEY, print_r($value, true));
            self::assertStringNotContainsString(self::API_KEY, (string) json_encode($value));
        }
    }

    /** @return iterable<string, array{int, class-string<ApiException>}> */
    public static function errorClasses(): iterable
    {
        yield '400' => [400, ValidationException::class];
        yield '422' => [422, ValidationException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '402' => [402, PaymentRequiredException::class];
        yield '403' => [403, PermissionException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, ConflictException::class];
        yield '429' => [429, RateLimitException::class];
        yield '500' => [500, ApiException::class];
    }

    /** @param class-string<ApiException> $class */
    #[DataProvider('errorClasses')]
    public function testErrorClasses(int $status, string $class): void
    {
        $client = $this->client([self::failure($status, 'SOME_CODE', 'Something')]);
        try {
            $client->lists->get(1);
            self::fail('no exception');
        } catch (ApiException $error) {
            self::assertSame($class, $error::class);
            self::assertSame($status, $error->statusCode);
            self::assertSame($status, $error->getCode());
        }
    }

    public function testErrorFields(): void
    {
        $client = $this->client([self::json(422, [
            'ok' => false,
            'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Invalid email', 'details' => [['field' => 'email', 'reason' => 'invalid', 'value' => 'x']]],
            'meta' => ['requestId' => 'req_1'],
        ])]);
        try {
            $client->lists->contacts->create(1, ['email' => 'x']);
            self::fail('no exception');
        } catch (ValidationException $error) {
            self::assertSame('VALIDATION_ERROR', $error->errorCode);
            self::assertSame('req_1', $error->requestId);
            self::assertSame([['field' => 'email', 'reason' => 'invalid', 'value' => 'x']], $error->details);
            self::assertStringContainsString('Invalid email', $error->getMessage());
            self::assertStringContainsString('req_1', $error->getMessage());
        }
    }

    public function testErrorsSurviveSerialize(): void
    {
        $error = new RateLimitException('Slow down', 'RATE_LIMITED', 429, [['reason' => 'too many']], 'r1', ['Retry-After' => ['7']], 7.0);
        $restored = unserialize(serialize($error));
        self::assertInstanceOf(RateLimitException::class, $restored);
        self::assertSame(7.0, $restored->retryAfter);
        self::assertSame($error->getMessage(), $restored->getMessage());
    }

    public function testRateLimitIsNotRetried(): void
    {
        $client = $this->client([self::failure(429, 'RATE_LIMITED', 'Slow down', null, ['Retry-After' => '7'])]);
        try {
            $client->lists->get(1);
            self::fail('no exception');
        } catch (RateLimitException $error) {
            self::assertSame(7.0, $error->retryAfter);
        }
        self::assertCount(1, $this->history);
    }

    public function testNonJsonErrorBody(): void
    {
        $client = $this->client([new Response(500, [], '<html>Internal error</html>')]);
        try {
            $client->lists->get(1);
            self::fail('no exception');
        } catch (ApiException $error) {
            self::assertSame('HTTP_500', $error->errorCode);
            self::assertStringContainsString('<html>Internal error', $error->getMessage());
        }
    }

    public function testMissingRouteKeepsTheServerMessage(): void
    {
        $client = $this->client([self::json(404, ['message' => 'Cannot GET /api/v1/public/nope', 'statusCode' => 404])]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Cannot GET /api/v1/public/nope');
        $client->lists->get(1);
    }

    /** @return iterable<string, array{Response}> */
    public static function outsideTheContract(): iterable
    {
        yield 'HTML page' => [new Response(200, ['Content-Type' => 'text/html'], '<html>Login to Wi-Fi</html>')];
        yield 'JSON without envelope' => [self::json(200, ['id' => 1])];
        yield 'ok false with 200' => [self::json(200, ['ok' => false])];
        yield 'empty body' => [new Response(200)];
        yield 'data is not an object' => [self::ok('text')];
    }

    #[DataProvider('outsideTheContract')]
    public function testResponseOutsideTheContract(Response $response): void
    {
        $client = $this->client([$response]);
        $this->expectException(UnexpectedResponseException::class);
        $client->lists->get(1);
    }

    public function testListWithoutItemsOrPagination(): void
    {
        $client = $this->client([self::ok([], self::PAGINATION), self::ok(['items' => []])]);
        try {
            $client->lists->list();
            self::fail('no exception');
        } catch (UnexpectedResponseException $error) {
            self::assertStringContainsString('no items', $error->getMessage());
        }
        $this->expectExceptionMessage('no pagination');
        $client->lists->list();
    }

    public function testResponseInfo(): void
    {
        $client = $this->client([self::json(200, [
            'ok' => true,
            'data' => ['id' => 7, 'name' => 'Clients', 'futureField' => 1],
            'meta' => ['requestId' => 'req_7'],
        ], ['X-RateLimit-Limit' => '100', 'X-RateLimit-Remaining' => '99', 'X-RateLimit-Reset' => '1795000000'])]);
        $info = new ResponseInfo();
        $list = $client->lists->get(7, ['response_info' => $info]);
        self::assertSame('Clients', $list->name);
        self::assertSame('req_7', $info->requestId);
        self::assertNotNull($info->rateLimit);
        self::assertSame([100, 99], [$info->rateLimit->limit, $info->rateLimit->remaining]);
        self::assertSame(1795000000, $info->rateLimit->resetAt->getTimestamp());
        self::assertSame(200, $info->response?->getStatusCode());
        self::assertSame(1, $info->raw['data']['futureField'] ?? null);
    }

    public function testUnknownFieldsAndArrayAccess(): void
    {
        $client = $this->client([self::ok(['id' => 7, 'name' => 'Clients', 'futureField' => ['a' => 1]])]);
        $list = $client->lists->get(7);
        // a field the SDK does not know yet: read it as an array key, PHPStan knows only the documented properties
        self::assertSame(['a' => 1], $list['futureField']);
        self::assertSame('Clients', $list->name);
        self::assertSame('Clients', $list['name']);
        self::assertTrue(isset($list['name']));
        self::assertNull($list['missing']);
        self::assertSame(['id' => 7, 'name' => 'Clients', 'futureField' => ['a' => 1]], $list->toArray());
    }

    public function testResponseObjectsAreReadOnly(): void
    {
        $list = $this->client([self::ok(['id' => 7])])->lists->get(7);
        $this->expectException(\LogicException::class);
        $list['id'] = 8;
    }

    public function testDatesBecomeDateTimeImmutable(): void
    {
        $client = $this->client([self::ok([
            'id' => 7,
            'createdAt' => '2026-11-12T12:00:00.000Z',
            'updatedAt' => '2026-11-12T13:00:00.123456789Z',
            'variables' => ['createdAt' => '2026-11-12T12:00:00.000Z'],
            'date' => '28.08.2026',
        ])]);
        $list = $client->lists->get(7);
        self::assertInstanceOf(\DateTimeImmutable::class, $list->createdAt);
        self::assertSame('2026-11-12T12:00:00.000+00:00', $list->createdAt->format('Y-m-d\TH:i:s.vP'));
        self::assertInstanceOf(\DateTimeImmutable::class, $list->updatedAt);
        self::assertSame('123456', $list->updatedAt->format('u'));
        self::assertSame(['createdAt' => '2026-11-12T12:00:00.000Z'], $list['variables']);
        self::assertSame('28.08.2026', $list['date']);
    }

    // --- retries, idempotency, timeouts -------------------------------------------------------------------------

    public function testPostKeepsTheIdempotencyKeyAcrossRetries(): void
    {
        $client = $this->client([self::failure(503, 'UNAVAILABLE'), self::ok(['id' => 1])]);
        $client->lists->create(['name' => 'Clients']);
        $first = $this->request(0)->getHeaderLine('Idempotency-Key');
        self::assertSame($first, $this->request(1)->getHeaderLine('Idempotency-Key'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $first);
        self::assertSame((string) $this->request(0)->getBody(), (string) $this->request(1)->getBody());
    }

    public function testCallerIdempotencyKey(): void
    {
        $client = $this->client([self::ok(['id' => 1])]);
        $client->lists->create(['name' => 'Clients'], ['idempotency_key' => 'order-42']);
        self::assertSame('order-42', $this->request()->getHeaderLine('Idempotency-Key'));
    }

    public function testRetriesInProgressConflictAfterRetryAfter(): void
    {
        $client = $this->client([self::failure(409, 'REQUEST_ALREADY_IN_PROGRESS', 'In progress', null, ['Retry-After' => '2']), self::ok(['id' => 1])]);
        $client->lists->create(['name' => 'Clients']);
        self::assertSame([2.0], $this->delays);
    }

    /** @return iterable<string, array{Response}> */
    public static function notRetried(): iterable
    {
        yield 'conflict' => [self::failure(409, 'LIST_NAME_TAKEN')];
        yield '500' => [self::failure(500, 'INTERNAL')];
        yield '429' => [self::failure(429, 'LIMIT', 'Slow', null, ['Retry-After' => '1'])];
    }

    #[DataProvider('notRetried')]
    public function testOtherErrorsAreNotRetried(Response $response): void
    {
        $client = $this->client([$response]);
        try {
            $client->lists->create(['name' => 'x']);
            self::fail('no exception');
        } catch (ApiException) {
        }
        self::assertCount(1, $this->history);
    }

    public function testGivesUpAfterMaxRetriesWithBackoff(): void
    {
        $client = $this->client([self::failure(502, 'BAD_GATEWAY'), self::failure(503, 'UNAVAILABLE'), self::failure(504, 'TIMEOUT')]);
        try {
            $client->lists->get(1);
            self::fail('no exception');
        } catch (ApiException $error) {
            self::assertSame(504, $error->statusCode);
        }
        self::assertCount(3, $this->history);
        self::assertGreaterThanOrEqual(0.375, $this->delays[0]);
        self::assertLessThanOrEqual(0.625, $this->delays[0]);
        self::assertGreaterThanOrEqual(0.75, $this->delays[1]);
        self::assertLessThanOrEqual(1.25, $this->delays[1]);
    }

    public function testLongRetryAfterIsNotWaited(): void
    {
        $client = $this->client([self::failure(503, 'UNAVAILABLE', 'Down', null, ['Retry-After' => '120'])]);
        try {
            $client->lists->get(1);
        } catch (ApiException) {
        }
        self::assertCount(1, $this->history);
    }

    public function testNetworkErrorsAreRetriedThenThrown(): void
    {
        $request = new Psr7Request('GET', 'https://api.rusender.ru');
        $client = $this->client([new ConnectException('Connection reset', $request), self::ok(['id' => 1])]);
        $client->lists->get(1);
        self::assertCount(2, $this->history);

        $client = $this->client([new ConnectException('Connection refused', $request)], ['max_retries' => 0]);
        try {
            $client->lists->get(1);
            self::fail('no exception');
        } catch (NetworkException $error) {
            self::assertStringContainsString('refused', $error->getMessage());
            self::assertInstanceOf(ConnectException::class, $error->getPrevious());
        }
    }

    public function testTimeout(): void
    {
        $request = new Psr7Request('GET', 'https://api.rusender.ru');
        $client = $this->client([new ConnectException('cURL error 28: Operation timed out after 30001 milliseconds', $request)], ['max_retries' => 0]);
        $this->expectException(TimeoutException::class);
        $client->lists->get(1);
    }

    public function testNonIdempotentOperationIsNotRetriedAndSendsNoKey(): void
    {
        $client = $this->client([self::failure(503, 'UNAVAILABLE')]);
        try {
            $client->webhooks->test(5, ['idempotency_key' => 'ignored']);
        } catch (ApiException) {
        }
        self::assertCount(1, $this->history);
        self::assertFalse($this->request()->hasHeader('Idempotency-Key'));
    }

    public function testMaxRetriesPerRequest(): void
    {
        $client = $this->client([self::failure(503, 'UNAVAILABLE')]);
        try {
            $client->lists->get(1, ['max_retries' => 0]);
        } catch (ApiException) {
        }
        self::assertCount(1, $this->history);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidSettings(): iterable
    {
        yield 'zero timeout' => [['timeout' => 0]];
        yield 'negative timeout' => [['timeout' => -1]];
        yield 'negative retries' => [['max_retries' => -1]];
    }

    /** @param array<string, mixed> $settings */
    #[DataProvider('invalidSettings')]
    public function testInvalidSettings(array $settings): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client(self::API_KEY, $settings);
    }

    public function testInvalidRequestSettingsAreRejectedBeforeSending(): void
    {
        $client = $this->client();
        try {
            $client->me->get(['timeout' => 0]);
            self::fail('no exception');
        } catch (\InvalidArgumentException) {
        }
        self::assertSame([], $this->history);
    }
}
