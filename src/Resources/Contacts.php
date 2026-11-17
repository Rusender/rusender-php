<?php

declare(strict_types=1);

namespace RuSender\Resources;

use Psr\Http\Message\ResponseInterface;
use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\ApiSendingHistoryItem;
use RuSender\Types\BulkDeleteResult;
use RuSender\Types\CampaignSendingHistoryItem;
use RuSender\Types\Contact;
use RuSender\Types\ContactStatistics;
use RuSender\Types\DeleteResult;
use RuSender\Types\Page;
use RuSender\Types\RemoveFromAllListsResult;

/**
 * Contacts across all lists: search, update, subscription, export, sending history.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type ListContactsParams array{
 *     page?: int,
 *     limit?: int,
 *     email?: string,
 *     status?: list<string>,
 *     listId?: int,
 *     sortBy?: 'id'|'email'|'createdAt'|'updatedAt'|'lastActivityAt',
 *     sortDir?: 'asc'|'desc',
 * }
 * @phpstan-type UpdateContactParams array{name?: string, phone?: string}
 * @phpstan-type ExportContactsParams array{
 *     format: 'csv'|'json',
 *     email?: string,
 *     status?: list<string>,
 *     sortBy?: 'id'|'email'|'createdAt'|'updatedAt'|'lastActivityAt',
 *     sortDir?: 'asc'|'desc',
 *     limit?: int,
 * }
 * @phpstan-type PageParams array{page?: int, limit?: int}
 */
final class Contacts extends AbstractResource
{
    /**
     * Lists contacts across all lists, filterable by email, status and list.
     * Scope: `contacts.read`.
     *
     * @param ListContactsParams $params
     * @param RequestOptions     $options
     *
     * @return Page<Contact>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/contacts', $params), $options, Contact::class);
    }

    /**
     * Gets a contact with its lists and variables.
     * Scope: `contacts.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $contactId, array $options = []): Contact
    {
        return $this->call(Request::get(Path::build('/v1/public/contacts/{}', $contactId)), $options, Contact::class);
    }

    /**
     * Updates a contact's name and phone; an empty string clears a value. To change variables, use
     * lists->contacts->update().
     * Scope: `contacts.write`.
     *
     * @param UpdateContactParams $params
     * @param RequestOptions      $options
     */
    public function update(int $contactId, array $params, array $options = []): Contact
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/contacts/{}', $contactId), $params), $options, Contact::class);
    }

    /**
     * Permanently deletes a contact from all lists.
     * Scope: `contacts.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $contactId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/contacts/{}', $contactId)), $options, DeleteResult::class);
    }

    /**
     * Unsubscribes a contact from all campaigns.
     * Scope: `contacts.write`.
     *
     * @param RequestOptions $options
     */
    public function unsubscribe(int $contactId, array $options = []): Contact
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/contacts/{}/unsubscribe', $contactId)), $options, Contact::class);
    }

    /**
     * Restores the subscription of a contact you unsubscribed (status `unsubscribed_by_us`). A contact who
     * unsubscribed via the link cannot be resubscribed: ConflictException.
     * Scope: `contacts.write`.
     *
     * @param RequestOptions $options
     */
    public function subscribeBack(int $contactId, array $options = []): Contact
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/contacts/{}/subscribe-back', $contactId)), $options, Contact::class);
    }

    /**
     * Counts contacts by status — for the whole account or for one list (`listId`).
     * Scope: `contacts.read`.
     *
     * @param array{listId?: int} $params
     * @param RequestOptions      $options
     */
    public function getStatistics(array $params = [], array $options = []): ContactStatistics
    {
        return $this->call(Request::get('/v1/public/contacts/statistics', $params), $options, ContactStatistics::class);
    }

    /**
     * Deletes several contacts at once.
     * Scope: `contacts.write`.
     *
     * @param list<int>      $contactIds
     * @param RequestOptions $options
     */
    public function bulkDelete(array $contactIds, array $options = []): BulkDeleteResult
    {
        return $this->call(Request::withBody('POST', '/v1/public/contacts/bulk-delete', ['contactIds' => $contactIds]), $options, BulkDeleteResult::class);
    }

    /**
     * Removes contacts from all lists without deleting them.
     * Scope: `contacts.write`.
     *
     * @param list<int>      $contactIds
     * @param RequestOptions $options
     */
    public function removeFromAllLists(array $contactIds, array $options = []): RemoveFromAllListsResult
    {
        $request = Request::withBody('POST', '/v1/public/contacts/remove-from-all-lists', ['contactIds' => $contactIds]);

        return $this->call($request, $options, RemoveFromAllListsResult::class);
    }

    /**
     * Exports contacts as CSV or JSON. Returns the raw response with a streamed body: read it with
     * `(string) $response->getBody()` or copy the stream.
     * Scope: `contacts.read`.
     *
     * @param ExportContactsParams $params
     * @param RequestOptions       $options
     */
    public function export(array $params, array $options = []): ResponseInterface
    {
        return $this->raw(Request::get('/v1/public/contacts/export', $params), $options);
    }

    /**
     * Lists campaigns the contact received.
     * Scope: `contacts.read`.
     *
     * @param PageParams     $params
     * @param RequestOptions $options
     *
     * @return Page<CampaignSendingHistoryItem>
     */
    public function listCampaignHistory(int $contactId, array $params = [], array $options = []): Page
    {
        $request = Request::get(Path::build('/v1/public/contacts/{}/distribution-sending-history', $contactId), $params);

        return $this->page($request, $options, CampaignSendingHistoryItem::class);
    }

    /**
     * Lists transactional emails (API and SMTP) the contact received.
     * Scope: `contacts.read`.
     *
     * @param PageParams     $params
     * @param RequestOptions $options
     *
     * @return Page<ApiSendingHistoryItem>
     */
    public function listApiHistory(int $contactId, array $params = [], array $options = []): Page
    {
        $request = Request::get(Path::build('/v1/public/contacts/{}/api-sending-history', $contactId), $params);

        return $this->page($request, $options, ApiSendingHistoryItem::class);
    }
}
