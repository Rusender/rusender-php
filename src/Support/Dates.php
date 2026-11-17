<?php

declare(strict_types=1);

namespace RuSender\Support;

/** @internal */
final class Dates
{
    /**
     * Response fields that hold a moment in time (ISO 8601 date-time in the API): the SDK turns them into
     * DateTimeImmutable. Calendar days without time (`date` of campaign activity, `dateFrom`/`dateTo` of key
     * statistics) stay strings: a moment for a day depends on the time zone and shifts the day.
     * tests/ContractTest.php keeps the list in sync with the spec.
     */
    public const DATE_TIME_FIELDS = [
        'checkedAt',
        'completedAt',
        'createdAt',
        'finishedAt',
        'lastActivityAt',
        'occurredAt',
        'scheduledAt',
        'scheduledSendDate',
        'sentAt',
        'startedAt',
        'updatedAt',
        'verifiedAt',
    ];

    /** Keys whose values are user data (contact variables): never converted, whatever their names. */
    private const OPAQUE_FIELDS = ['variables'];

    /** Replaces date-time strings of known fields with DateTimeImmutable, recursively. */
    public static function revive(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }
        $result = [];
        foreach ($value as $key => $item) {
            if (\in_array($key, self::OPAQUE_FIELDS, true)) {
                $result[$key] = $item;
            } elseif (\is_string($key) && \in_array($key, self::DATE_TIME_FIELDS, true)) {
                $result[$key] = self::toDate($item);
            } else {
                $result[$key] = self::revive($item);
            }
        }

        return $result;
    }

    /** A date parameter as ISO 8601 in UTC with milliseconds; strings are sent as they are. */
    public static function iso(\DateTimeInterface $value): string
    {
        return \DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');
    }

    private static function toDate(mixed $value): mixed
    {
        if (!\is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value) !== 1) {
            return $value;
        }
        // PHP keeps 6 fraction digits at most: an API value with more would not parse
        $normalized = preg_replace('/(\.\d{6})\d+/', '$1', $value) ?? $value;
        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Exception) {
            return $value;
        }
    }
}
