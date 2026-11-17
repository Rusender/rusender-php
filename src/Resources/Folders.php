<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Folder;
use RuSender\Types\FolderCampaignsResult;
use RuSender\Types\Page;

/**
 * Campaign folders.
 *
 * @phpstan-import-type RequestOptions from Transport
 */
final class Folders extends AbstractResource
{
    /**
     * Lists campaign folders, searchable by name (`q`).
     * Scope: `campaigns.read`.
     *
     * @param array{page?: int, limit?: int, q?: string} $params
     * @param RequestOptions                              $options
     *
     * @return Page<Folder>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/folders', $params), $options, Folder::class);
    }

    /**
     * Creates a campaign folder.
     * Scope: `campaigns.write`.
     *
     * @param array{name: string} $params
     * @param RequestOptions      $options
     */
    public function create(array $params, array $options = []): Folder
    {
        return $this->call(Request::withBody('POST', '/v1/public/folders', $params), $options, Folder::class);
    }

    /**
     * Gets a campaign folder.
     * Scope: `campaigns.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $folderId, array $options = []): Folder
    {
        return $this->call(Request::get(Path::build('/v1/public/folders/{}', $folderId)), $options, Folder::class);
    }

    /**
     * Renames a campaign folder.
     * Scope: `campaigns.write`.
     *
     * @param array{name?: string} $params
     * @param RequestOptions       $options
     */
    public function update(int $folderId, array $params, array $options = []): Folder
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/folders/{}', $folderId), $params), $options, Folder::class);
    }

    /**
     * Deletes a campaign folder. The campaigns in it are not deleted, only taken out of the folder.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $folderId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/folders/{}', $folderId)), $options, DeleteResult::class);
    }

    /**
     * Puts campaigns into a folder. A campaign belongs to one folder, so it moves from its current folder. Unknown
     * ids are ignored; `affectedCampaigns` is the number of campaigns actually added.
     * Scope: `campaigns.write`.
     *
     * @param list<int>      $campaignIds
     * @param RequestOptions $options
     */
    public function addCampaigns(int $folderId, array $campaignIds, array $options = []): FolderCampaignsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/folders/{}/campaigns/add', $folderId), ['campaignIds' => $campaignIds]);

        return $this->call($request, $options, FolderCampaignsResult::class);
    }

    /**
     * Takes campaigns out of a folder without deleting them. Unknown ids are ignored; `affectedCampaigns` is the
     * number of campaigns actually removed.
     * Scope: `campaigns.write`.
     *
     * @param list<int>      $campaignIds
     * @param RequestOptions $options
     */
    public function removeCampaigns(int $folderId, array $campaignIds, array $options = []): FolderCampaignsResult
    {
        $request = Request::withBody('POST', Path::build('/v1/public/folders/{}/campaigns/remove', $folderId), ['campaignIds' => $campaignIds]);

        return $this->call($request, $options, FolderCampaignsResult::class);
    }
}
