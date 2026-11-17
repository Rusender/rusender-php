<?php

declare(strict_types=1);

namespace RuSender\Tests\Support;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RuSender\Client;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const API_KEY = 'rs_ck_v1_test_secret';

    /** @var list<array{request: RequestInterface}> */
    protected array $history = [];

    /** @var list<float> */
    protected array $delays = [];

    /**
     * A client whose requests are answered by the steps in order; retries do not wait, their delays are recorded.
     *
     * @param list<mixed>          $steps    responses or exceptions
     * @param array<string, mixed> $options
     */
    protected function client(array $steps = [], array $options = []): Client
    {
        $this->history = [];
        $this->delays = [];
        $stack = HandlerStack::create(new MockHandler($steps));
        $history = &$this->history;
        $stack->push(Middleware::history($history));
        $client = new Client(self::API_KEY, $options + ['http_client' => new GuzzleClient(['handler' => $stack])]);
        $transport = (new \ReflectionProperty(Client::class, 'transport'))->getValue($client);
        \assert($transport instanceof \RuSender\Http\Transport);
        $transport->sleep = function (float $seconds): void {
            $this->delays[] = $seconds;
        };

        return $client;
    }

    protected function request(int $index = 0): RequestInterface
    {
        self::assertArrayHasKey($index, $this->history, \sprintf('request %d was not sent', $index));

        return $this->history[$index]['request'];
    }

    /** @return mixed the `data` of a JSON request body */
    protected function data(int $index = 0): mixed
    {
        $body = json_decode((string) $this->request($index)->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        return $body['data'];
    }

    /**
     * @param array<string, mixed>     $meta
     * @param array<string, string>    $headers
     */
    protected static function ok(mixed $data, array $meta = [], int $status = 200, array $headers = []): Response
    {
        return self::json($status, [
            'ok' => true,
            'data' => $data,
            'meta' => ['requestId' => 'req_ok', 'timestamp' => '2026-11-11T00:00:00.000Z'] + $meta,
        ], $headers);
    }

    /**
     * @param list<array<string, mixed>>|null $details
     * @param array<string, string>           $headers
     */
    protected static function failure(int $status, string $code, string $message = 'Error', ?array $details = null, array $headers = []): Response
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return self::json($status, ['ok' => false, 'error' => $error, 'meta' => ['requestId' => 'req_err']], $headers);
    }

    /** @param array<string, string> $headers */
    protected static function json(int $status, mixed $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, json_encode($body, \JSON_THROW_ON_ERROR));
    }

    protected const PAGINATION = ['pagination' => ['page' => 1, 'limit' => 20, 'totalItems' => 0, 'totalPages' => 0]];
}
