<?php

declare(strict_types=1);

namespace RuSender\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * What came with the data of a call. Pass an instance as the `response_info` request option to have it filled:
 *
 *     $info = new ResponseInfo();
 *     $list = $rusender->lists->get(7, ['response_info' => $info]);
 *     echo $info->requestId;
 */
final class ResponseInfo
{
    /** Server-side request identifier — include it when you contact support. */
    public ?string $requestId = null;

    /** Null when the response has no X-RateLimit-* headers. */
    public ?RateLimit $rateLimit = null;

    /** The raw HTTP response; its body has already been read. */
    public ?ResponseInterface $response = null;

    /** @var array<string, mixed>|null the whole JSON of the response, including fields the SDK does not know yet */
    public ?array $raw = null;
}
