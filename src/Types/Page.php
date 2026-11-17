<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * One page of a paginated list. Request the next page with `page + 1`.
 *
 * @template T of ApiObject
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T>             $items
     * @param array<string, mixed> $extra other fields of the list response
     */
    public function __construct(
        public readonly array $items,
        public readonly Pagination $pagination,
        public readonly array $extra = [],
    ) {}

    /** @return \ArrayIterator<int, T> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
