<?php

declare(strict_types=1);

namespace RuSender\Resources;

use Psr\Http\Message\ResponseInterface;
use RuSender\Exceptions\UnexpectedResponseException;
use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Types\ApiObject;
use RuSender\Types\CursorPage;
use RuSender\Types\Page;
use RuSender\Types\Pagination;

/**
 * @phpstan-import-type RequestOptions from Transport
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Transport $transport) {}

    /**
     * @template T of ApiObject
     *
     * @param RequestOptions  $options
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function call(Request $request, array $options, string $class): ApiObject
    {
        return $class::from(self::object($request, $this->transport->request($request, $options)['data']));
    }

    /**
     * A page/limit list: items from data, pagination from meta.pagination.
     *
     * @template T of ApiObject
     *
     * @param RequestOptions  $options
     * @param class-string<T> $class
     *
     * @return Page<T>
     */
    protected function page(Request $request, array $options, string $class): Page
    {
        $result = $this->transport->request($request, $options);
        $data = self::object($request, $result['data']);
        $pagination = $result['meta']['pagination'] ?? null;
        if (!\is_array($pagination)) {
            throw new UnexpectedResponseException(\sprintf('Unexpected response from RuSender API: %s %s returned no pagination', $request->method, $request->path));
        }
        $items = self::itemsOf($request, $data);
        $extra = [];
        foreach ($data as $key => $value) {
            if ($key !== 'items') {
                $extra[(string) $key] = $value;
            }
        }

        return new Page($class::list($items), Pagination::from($pagination), $extra);
    }

    /**
     * @template T of ApiObject
     *
     * @param RequestOptions  $options
     * @param class-string<T> $class
     *
     * @return CursorPage<T>
     */
    protected function cursorPage(Request $request, array $options, string $class): CursorPage
    {
        $data = self::object($request, $this->transport->request($request, $options)['data']);
        $cursor = $data['nextCursor'] ?? null;

        return new CursorPage($class::list(self::itemsOf($request, $data)), (bool) ($data['hasMore'] ?? false), \is_string($cursor) ? $cursor : null);
    }

    /**
     * A list without pagination: the API returns all items at once.
     *
     * @template T of ApiObject
     *
     * @param RequestOptions  $options
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    protected function items(Request $request, array $options, string $class): array
    {
        return $class::list(self::itemsOf($request, $this->transport->request($request, $options)['data']));
    }

    /**
     * A response that is a file or a stream: read or copy its body.
     *
     * @param RequestOptions $options
     */
    protected function raw(Request $request, array $options): ResponseInterface
    {
        return $this->transport->requestRaw($request, $options);
    }

    /**
     * A list response must carry an items array; anything else is a broken contract, not an empty list.
     *
     * @return array<array-key, mixed>
     */
    protected static function itemsOf(Request $request, mixed $data): array
    {
        $items = \is_array($data) ? ($data['items'] ?? null) : null;
        if (!\is_array($items)) {
            throw new UnexpectedResponseException(\sprintf('Unexpected response from RuSender API: %s %s returned no items array', $request->method, $request->path));
        }

        return $items;
    }

    /** @return array<array-key, mixed> */
    private static function object(Request $request, mixed $data): array
    {
        if (!\is_array($data)) {
            throw new UnexpectedResponseException(\sprintf('Unexpected response from RuSender API: %s %s returned no data object', $request->method, $request->path));
        }

        return $data;
    }
}
