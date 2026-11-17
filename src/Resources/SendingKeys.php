<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Page;
use RuSender\Types\SendingKey;
use RuSender\Types\SendingKeyStats;

/**
 * Sending keys for transactional emails: domain, statistics, default key.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class SendingKeys extends AbstractResource
{
    /**
     * Lists sending keys for transactional emails.
     * Scope: `sending_keys.read`.
     *
     * @param array{page?: int, limit?: int} $params
     * @param RequestOptions                 $options
     *
     * @return Page<SendingKey>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/sending-keys', $params), $options, SendingKey::class);
    }

    /**
     * Creates a sending key for a verified domain. The key starts `disabled` and becomes `enabled` after moderation.
     * Scope: `sending_keys.write`.
     *
     * @param array{name: string, domainId: int} $params
     * @param RequestOptions                     $options
     */
    public function create(array $params, array $options = []): SendingKey
    {
        return $this->call(Request::withBody('POST', '/v1/public/sending-keys', $params), $options, SendingKey::class);
    }

    /**
     * Gets a sending key.
     * Scope: `sending_keys.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $keyId, array $options = []): SendingKey
    {
        return $this->call(Request::get(Path::build('/v1/public/sending-keys/{}', $keyId)), $options, SendingKey::class);
    }

    /**
     * Renames a sending key or, with `isDefault => true`, makes it the default key of its domain.
     * Scope: `sending_keys.write`.
     *
     * @param array{name?: string, isDefault?: true} $params
     * @param RequestOptions                         $options
     */
    public function update(int $keyId, array $params, array $options = []): SendingKey
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/sending-keys/{}', $keyId), $params), $options, SendingKey::class);
    }

    /**
     * Deletes a sending key. Sending with it stops immediately.
     * Scope: `sending_keys.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $keyId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/sending-keys/{}', $keyId)), $options, DeleteResult::class);
    }

    /**
     * Gets sending statistics of a key for a period, calendar days YYYY-MM-DD; the last 30 days without
     * `dateFrom`/`dateTo`.
     * Scope: `sending_keys.read`.
     *
     * @param array{dateFrom?: string, dateTo?: string} $params
     * @param RequestOptions                            $options
     */
    public function getStatistics(int $keyId, array $params = [], array $options = []): SendingKeyStats
    {
        return $this->call(Request::get(Path::build('/v1/public/sending-keys/{}/stats', $keyId), $params), $options, SendingKeyStats::class);
    }
}
