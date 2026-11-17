<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A response object: a thin read-only wrapper over the JSON of the API. Fields are read as properties
 * (`$email->status`) or array keys (`$email['status']`), with the names of the API. A field the SDK does not know
 * yet is available too; `toArray()` returns everything.
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class ApiObject implements \ArrayAccess, \JsonSerializable
{
    /**
     * Fields holding nested objects: key => class for an object, key => [class] for a list of them.
     *
     * @var array<string, class-string<ApiObject>|array{class-string<ApiObject>}>
     */
    protected const NESTED = [];

    /** @param array<string, mixed> $attributes */
    final public function __construct(private readonly array $attributes) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): static
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            $nested = static::NESTED[$key] ?? null;
            if ($nested !== null && \is_array($value)) {
                $value = \is_array($nested)
                    ? array_map(static fn(mixed $item): mixed => \is_array($item) ? $nested[0]::from($item) : $item, array_values($value))
                    : $nested::from($value);
            }
            $attributes[(string) $key] = $value;
        }

        return new static($attributes);
    }

    /**
     * @param array<array-key, mixed> $items
     *
     * @return list<static>
     */
    public static function list(array $items): array
    {
        return array_values(array_map(static fn(mixed $item): static => static::from(\is_array($item) ? $item : []), $items));
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new \LogicException('Response objects are read-only');
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Response objects are read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Response objects are read-only');
    }

    /**
     * Everything the API returned, nested objects as arrays; dates stay DateTimeImmutable.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(self::plain(...), $this->attributes);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return array_map(self::json(...), $this->toArray());
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return $this->attributes;
    }

    private static function plain(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        return \is_array($value) ? array_map(self::plain(...), $value) : $value;
    }

    private static function json(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::RFC3339_EXTENDED);
        }

        return \is_array($value) ? array_map(self::json(...), $value) : $value;
    }
}
