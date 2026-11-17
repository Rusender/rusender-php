<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * One page of a cursor-paginated list. Request the next page with `cursor` set to `$page->nextCursor`.
 *
 * @template T of ApiObject
 *
 * @implements \IteratorAggregate<int, T>
 */
final class CursorPage implements \IteratorAggregate, \Countable
{
    /** @param list<T> $items */
    public function __construct(
        public readonly array $items,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor,
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
