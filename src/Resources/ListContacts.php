<?php

declare(strict_types=1);

namespace RuSender\Resources;

use Psr\Http\Message\ResponseInterface;
use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\Contact;
use RuSender\Types\ContactUpsertResult;
use RuSender\Types\ImportContactsResult;
use RuSender\Types\ListContactsResult;
use RuSender\Types\MoveContactsResult;
use RuSender\Types\Page;

/**
 * Contacts of a list: `$rusender->lists->contacts`.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type Variables array<string, string|null>
 * @phpstan-type ListContactsInListParams array{
 *     page?: int,
 *     limit?: int,
 *     email?: string,
 *     status?: list<string>,
 *     sortBy?: 'id'|'email'|'createdAt'|'updatedAt'|'lastActivityAt',
 *     sortDir?: 'asc'|'desc',
 * }
 * @phpstan-type CreateListContactParams array{email: string, name?: string, phone?: string, variables?: Variables}
 * @phpstan-type UpdateListContactParams array{name?: string, phone?: string, variables?: Variables}
 * @phpstan-type ImportContactRow array{email?: string, name?: string, phone?: string, variables?: Variables}
 * @phpstan-type ImportContactsParams array{
 *     rows: list<ImportContactRow>,
 *     variables?: list<array{key: string, name: string, type?: 'string'|'phone'}>,
 *     updateExisting?: bool,
 * }
 * @phpstan-type MoveContactsParams array{toListId: int, contactIds: list<int>}
 * @phpstan-type ExportListContactsParams array{
 *     format: 'csv'|'json',
 *     email?: string,
 *     status?: list<string>,
 *     sortBy?: 'id'|'email'|'createdAt'|'updatedAt'|'lastActivityAt',
 *     sortDir?: 'asc'|'desc',
 *     limit?: int,
 * }
 */
final class ListContacts extends AbstractResource
{
    /**
     * Lists contacts in a list, filterable by email and status.
     * Scope: `contacts.read`.
     *
     * @param ListContactsInListParams $params
     * @param RequestOptions           $options
     *
     * @return Page<Contact>
     */
    public function list(int $listId, array $params = [], array $options = []): Page
    {
        return $this->page(Request::get(Path::build('/v1/public/lists/{}/contacts', $listId), $params), $options, Contact::class);
    }

    /**
     * Adds a contact to a list. If a contact with this email exists, it is reused: `name` and `phone` are ignored,
     * `variables` are applied. `created` in the result tells the two apart.
     * Scope: `contacts.write`.
     *
     * @param CreateListContactParams $params
     * @param RequestOptions          $options
     */
    public function create(int $listId, array $params, array $options = []): ContactUpsertResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/lists/{}/contacts', $listId), $params);

        return $this->call($request, $options, ContactUpsertResult::class);
    }

    /**
     * Updates a contact in a list: name, phone and list variables (null resets a value).
     * Scope: `contacts.write`.
     *
     * @param UpdateListContactParams $params
     * @param RequestOptions          $options
     */
    public function update(int $listId, int $contactId, array $params, array $options = []): Contact
    {
        $request = Request::withBody('PATCH', Path::build('/v1/public/lists/{}/contacts/{}', $listId, $contactId), $params);

        return $this->call($request, $options, Contact::class);
    }

    /**
     * Adds existing contacts to a list.
     * Scope: `contacts.write`.
     *
     * @param list<int>      $contactIds
     * @param RequestOptions $options
     */
    public function add(int $listId, array $contactIds, array $options = []): ListContactsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/lists/{}/contacts/add', $listId), ['contactIds' => $contactIds]);

        return $this->call($request, $options, ListContactsResult::class);
    }

    /**
     * Removes contacts from a list. The contacts are neither deleted nor unsubscribed.
     * Scope: `contacts.write`.
     *
     * @param list<int>      $contactIds
     * @param RequestOptions $options
     */
    public function remove(int $listId, array $contactIds, array $options = []): ListContactsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/lists/{}/contacts/remove', $listId), ['contactIds' => $contactIds]);

        return $this->call($request, $options, ListContactsResult::class);
    }

    /**
     * Moves contacts from this list to another one (`toListId`).
     * Scope: `contacts.write`.
     *
     * @param MoveContactsParams $params
     * @param RequestOptions     $options
     */
    public function move(int $listId, array $params, array $options = []): MoveContactsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/lists/{}/contacts/move', $listId), $params);

        return $this->call($request, $options, MoveContactsResult::class);
    }

    /**
     * Imports contacts as rows. Runs synchronously; counts and sample errors are in the result. `variables`
     * creates list variables before the import; `updateExisting` is false by default.
     * Scope: `contacts.write`.
     *
     * @param ImportContactsParams $params
     * @param RequestOptions       $options
     */
    public function import(int $listId, array $params, array $options = []): ImportContactsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/lists/{}/contacts/import', $listId), $params);

        return $this->call($request, $options, ImportContactsResult::class);
    }

    /**
     * Exports the contacts of a list as CSV or JSON. Returns the raw response with a streamed body.
     * Scope: `contacts.read`.
     *
     * @param ExportListContactsParams $params
     * @param RequestOptions           $options
     */
    public function export(int $listId, array $params, array $options = []): ResponseInterface
    {
        return $this->raw(Request::get(Path::build('/v1/public/lists/{}/contacts/export', $listId), $params), $options);
    }
}
