<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\AbTestPreview;
use RuSender\Types\ArchiveResult;
use RuSender\Types\Campaign;
use RuSender\Types\CampaignActivity;
use RuSender\Types\CampaignDetails;
use RuSender\Types\CampaignDomainStat;
use RuSender\Types\CampaignListItem;
use RuSender\Types\CampaignRecipient;
use RuSender\Types\ChunksPreview;
use RuSender\Types\DeleteResult;
use RuSender\Types\LinkClicks;
use RuSender\Types\Page;

/**
 * Campaigns: drafts, start, pause, A/B tests, chunked sending, statistics.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type Utm array{utm_source?: string, utm_medium?: string, utm_campaign?: string}
 * @phpstan-type AbTestParams array{
 *     variants: list<array{subject: string, previewTitle?: string}>,
 *     type?: 'subject',
 *     percent?: int,
 * }
 * @phpstan-type CampaignParams array{
 *     name?: string,
 *     subject?: string,
 *     previewTitle?: string,
 *     templateId?: int,
 *     senderId?: int,
 *     senderName?: string,
 *     listIds?: list<int>,
 *     segmentId?: int,
 *     attachments?: list<int>,
 *     utm?: Utm,
 *     abTest?: AbTestParams,
 * }
 * @phpstan-type ListCampaignsParams array{
 *     page?: int,
 *     limit?: int,
 *     name?: string,
 *     status?: list<string>,
 *     withStats?: bool,
 *     archived?: bool,
 * }
 * @phpstan-type ScheduleCampaignParams array{
 *     scheduledStartDate?: \DateTimeInterface|string,
 *     followUp?: array{subject: string, offsetHours: int, previewTitle?: string},
 *     chunkSettings?: array{initialChunkSize: int, increaseChunkSizePercent?: int},
 * }
 * @phpstan-type PreviewChunksParams array{
 *     initialChunkSize: int,
 *     increaseChunkSizePercent?: int,
 *     scheduledStartDate?: \DateTimeInterface|string,
 * }
 * @phpstan-type ListCampaignRecipientsParams array{page?: int, limit?: int, status?: list<string>}
 */
final class Campaigns extends AbstractResource
{
    /**
     * Lists campaigns, filterable by name and status. `withStats => true` includes statistics, `archived => true`
     * lists archived campaigns.
     * Scope: `campaigns.read`.
     *
     * @param ListCampaignsParams $params
     * @param RequestOptions      $options
     *
     * @return Page<CampaignListItem>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/campaigns', $params), $options, CampaignListItem::class);
    }

    /**
     * Creates a campaign draft. All fields are optional and can be set later with update(); a template, a sender,
     * a subject and at least one list are required to start it. `attachments` are ids from files->upload().
     * Scope: `campaigns.write`.
     *
     * @param CampaignParams $params
     * @param RequestOptions $options
     */
    public function create(array $params = [], array $options = []): Campaign
    {
        return $this->call(Request::withBody('POST', '/v1/public/campaigns', $params), $options, Campaign::class);
    }

    /**
     * Gets a campaign. For A/B and follow-up campaigns also returns `abTest`, `followUp` or `parent`; statistics
     * (`stats`, `totalStats`) only with `withStats => true`.
     * Scope: `campaigns.read`.
     *
     * @param array{withStats?: bool} $params
     * @param RequestOptions          $options
     */
    public function get(int $campaignId, array $params = [], array $options = []): CampaignDetails
    {
        return $this->call(Request::get(Path::build('/v1/public/campaigns/{}', $campaignId), $params), $options, CampaignDetails::class);
    }

    /**
     * Updates a campaign draft; only the fields you pass change, `[]` clears a list. For A/B campaigns
     * `abTest.variants` replaces the whole set of variants.
     * Scope: `campaigns.write`.
     *
     * @param CampaignParams $params
     * @param RequestOptions $options
     */
    public function update(int $campaignId, array $params, array $options = []): Campaign
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/campaigns/{}', $campaignId), $params), $options, Campaign::class);
    }

    /**
     * Deletes a campaign draft. Only drafts can be deleted.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $campaignId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/campaigns/{}', $campaignId)), $options, DeleteResult::class);
    }

    /**
     * Starts a campaign draft. Sends real emails. The campaign goes to moderation and is sent after approval, or
     * at `scheduledStartDate`. Optionally adds a follow-up (`followUp`) or sends in daily chunks
     * (`chunkSettings`); neither is available for A/B campaigns.
     * Not enough balance → PaymentRequiredException with CAMPAIGN_INSUFFICIENT_BALANCE: top up and retry, the same
     * `idempotency_key` is fine. A failed start leaves the draft unchanged.
     * Scope: `campaigns.write`.
     *
     * @param ScheduleCampaignParams $params
     * @param RequestOptions         $options
     */
    public function schedule(int $campaignId, array $params = [], array $options = []): Campaign
    {
        return $this->call(Request::withBody('POST', Path::build('/v1/public/campaigns/{}/schedule', $campaignId), $params), $options, Campaign::class);
    }

    /**
     * Moves a completed or banned campaign to the archive. A/B and follow-up campaigns are archived as a whole.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function archive(int $campaignId, array $options = []): ArchiveResult
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/campaigns/{}/archive', $campaignId)), $options, ArchiveResult::class);
    }

    /**
     * Restores a campaign from the archive.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function unarchive(int $campaignId, array $options = []): ArchiveResult
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/campaigns/{}/unarchive', $campaignId)), $options, ArchiveResult::class);
    }

    /**
     * Pauses a campaign that is being sent. Emails already handed over for delivery are not recalled; for an A/B
     * campaign the whole experiment is paused. ValidationException with CAMPAIGN_NOT_PAUSABLE if the campaign is
     * not being sent, with CAMPAIGN_IS_AB_VARIANT for an A/B test variant — pause the base A/B campaign instead.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function pause(int $campaignId, array $options = []): Campaign
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/campaigns/{}/pause', $campaignId)), $options, Campaign::class);
    }

    /**
     * Resumes a paused campaign approved by moderation. Sends real emails; the campaign is checked again before
     * sending. ValidationException with CAMPAIGN_NOT_RESUMABLE if it cannot be resumed, with CAMPAIGN_IS_AB_VARIANT
     * for an A/B test variant — resume the base A/B campaign instead.
     * Scope: `campaigns.write`.
     *
     * @param RequestOptions $options
     */
    public function resume(int $campaignId, array $options = []): Campaign
    {
        return $this->call(Request::withoutBody('POST', Path::build('/v1/public/campaigns/{}/resume', $campaignId)), $options, Campaign::class);
    }

    /**
     * Calculates how an A/B test would split the audience for the given percent: contacts per variant and the share
     * that receives the winner. Does not change the campaign.
     * Scope: `campaigns.read`.
     *
     * @param array{percent?: int} $params
     * @param RequestOptions       $options
     */
    public function previewAbTest(int $campaignId, array $params = [], array $options = []): AbTestPreview
    {
        $request = Request::get(Path::build('/v1/public/campaigns/{}/ab-test/preview', $campaignId), $params);

        return $this->call($request, $options, AbTestPreview::class);
    }

    /**
     * Calculates the chunked sending plan: number of chunks, recipients and send date of each chunk. Does not change
     * the campaign; pass the same `chunkSettings` to schedule().
     * Scope: `campaigns.read`.
     *
     * @param PreviewChunksParams $params
     * @param RequestOptions      $options
     */
    public function previewChunks(int $campaignId, array $params, array $options = []): ChunksPreview
    {
        $request = Request::get(Path::build('/v1/public/campaigns/{}/chunks/preview', $campaignId), $params);

        return $this->call($request, $options, ChunksPreview::class);
    }

    /**
     * Gets opens and clicks by day, `date` as DD.MM.YYYY: 11 days from the campaign start, for chunked sending at
     * least the number of chunks.
     * Scope: `campaigns.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<CampaignActivity>
     */
    public function getActivity(int $campaignId, array $options = []): array
    {
        return $this->items(Request::get(Path::build('/v1/public/campaigns/{}/activity', $campaignId)), $options, CampaignActivity::class);
    }

    /**
     * Gets click counts per link.
     * Scope: `campaigns.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<LinkClicks>
     */
    public function getLinkClicks(int $campaignId, array $options = []): array
    {
        return $this->items(Request::get(Path::build('/v1/public/campaigns/{}/link-clicks', $campaignId)), $options, LinkClicks::class);
    }

    /**
     * Gets campaign statistics broken down by recipient mailbox domain.
     * Scope: `campaigns.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<CampaignDomainStat>
     */
    public function getStatsByDomains(int $campaignId, array $options = []): array
    {
        return $this->items(Request::get(Path::build('/v1/public/campaigns/{}/stats-by-domains', $campaignId)), $options, CampaignDomainStat::class);
    }

    /**
     * Lists campaign recipients with their delivery and engagement status, filterable by status. `hasMore` tells
     * whether there is a next page.
     * Scope: `campaigns.read`.
     *
     * @param ListCampaignRecipientsParams $params
     * @param RequestOptions               $options
     *
     * @return array{items: list<CampaignRecipient>, hasMore: bool}
     */
    public function listRecipients(int $campaignId, array $params = [], array $options = []): array
    {
        $request = Request::get(Path::build('/v1/public/campaigns/{}/recipients', $campaignId), $params);
        $data = $this->transport->request($request, $options)['data'];

        return [
            'items' => CampaignRecipient::list(self::itemsOf($request, $data)),
            'hasMore' => \is_array($data) && (bool) ($data['hasMore'] ?? false),
        ];
    }
}
