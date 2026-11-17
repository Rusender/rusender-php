<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Domain;
use RuSender\Types\DomainVerification;
use RuSender\Types\Page;

/**
 * Sender domains and their DNS verification.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Domains extends AbstractResource
{
    /**
     * Lists sending domains with their DNS records and verification status.
     * Scope: `domains.read`.
     *
     * @param array{page?: int, limit?: int} $params
     * @param RequestOptions                 $options
     *
     * @return Page<Domain>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/domains', $params), $options, Domain::class);
    }

    /**
     * Adds a sending domain. Add the DNS records from `dnsRecords` (DKIM, SPF, DMARC) to the domain's zone, then
     * call verify().
     * Scope: `domains.write`.
     *
     * @param array{name: string} $params
     * @param RequestOptions      $options
     */
    public function create(array $params, array $options = []): Domain
    {
        return $this->call(Request::withBody('POST', '/v1/public/domains', $params), $options, Domain::class);
    }

    /**
     * Gets a domain with its DNS records.
     * Scope: `domains.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $domainId, array $options = []): Domain
    {
        return $this->call(Request::get(Path::build('/v1/public/domains/{}', $domainId)), $options, Domain::class);
    }

    /**
     * Checks the domain's DKIM, SPF and DMARC records now and refreshes the verification status. May return
     * `warnings` and a `suggestedSpfRecord`.
     * Scope: `domains.write`.
     *
     * @param RequestOptions $options
     */
    public function verify(int $domainId, array $options = []): DomainVerification
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/domains/{}/verify', $domainId)), $options, DomainVerification::class);
    }

    /**
     * Deletes an unverified domain. Verified domains cannot be deleted via the API.
     * Scope: `domains.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $domainId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/domains/{}', $domainId)), $options, DeleteResult::class);
    }
}
