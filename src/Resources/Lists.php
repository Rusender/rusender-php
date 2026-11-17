<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\ListStatistics;
use RuSender\Types\MailingList;
use RuSender\Types\Page;

/**
 * Contact lists, their contacts (`lists->contacts`) and variables (`lists->variables`).
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type ListListsParams array{
 *     page?: int,
 *     limit?: int,
 *     q?: string,
 *     status?: 'enabled'|'disabled',
 *     sortBy?: 'id'|'name'|'createdAt'|'updatedAt',
 *     sortDir?: 'asc'|'desc',
 * }
 * @phpstan-type UpdateListParams array{name?: string, status?: 'enabled'|'disabled'}
 */
final class Lists extends AbstractResource
{
    /** Contacts of a list. */
    public readonly ListContacts $contacts;

    /** Variables of a list. */
    public readonly ListVariables $variables;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->contacts = new ListContacts($transport);
        $this->variables = new ListVariables($transport);
    }

    /**
     * Lists contact lists, searchable by name (`q`).
     * Scope: `contacts.read`.
     *
     * @param ListListsParams $params
     * @param RequestOptions  $options
     *
     * @return Page<MailingList>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/lists', $params), $options, MailingList::class);
    }

    /**
     * Creates a contact list.
     * Scope: `contacts.write`.
     *
     * @param array{name: string} $params
     * @param RequestOptions      $options
     */
    public function create(array $params, array $options = []): MailingList
    {
        return $this->call(Request::withBody('POST', '/v1/public/lists', $params), $options, MailingList::class);
    }

    /**
     * Gets a list with its contact count.
     * Scope: `contacts.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $listId, array $options = []): MailingList
    {
        return $this->call(Request::get(Path::build('/v1/public/lists/{}', $listId)), $options, MailingList::class);
    }

    /**
     * Renames a list or changes its status.
     * Scope: `contacts.write`.
     *
     * @param UpdateListParams $params
     * @param RequestOptions   $options
     */
    public function update(int $listId, array $params, array $options = []): MailingList
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/lists/{}', $listId), $params), $options, MailingList::class);
    }

    /**
     * Deletes a list together with contacts that belong only to this list.
     * Scope: `contacts.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $listId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/lists/{}', $listId)), $options, DeleteResult::class);
    }

    /**
     * Gets list statistics: contacts by status, sends, opens, errors, unsubscribes, complaints.
     * Scope: `contacts.read`.
     *
     * @param RequestOptions $options
     */
    public function getStatistics(int $listId, array $options = []): ListStatistics
    {
        return $this->call(Request::get(Path::build('/v1/public/lists/{}/statistics', $listId)), $options, ListStatistics::class);
    }
}
