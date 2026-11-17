<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A campaign with its A/B test, follow-up or parent.
 *
 * @property-read int                       $id
 * @property-read string                    $name
 * @property-read string|null               $subject
 * @property-read string|null               $previewTitle
 * @property-read string                    $type regular, ab_basic, ab_variant, follow_up_basic, follow_up or chunked
 * @property-read string                    $status draft, built, scheduled, in_progress, paused, completed, banned or on_hold
 * @property-read string                    $statusModerate not_ready_to_moderate, ready_to_moderate, moderating, approved, banned or paused
 * @property-read int|null                  $folderId
 * @property-read bool                      $isArchived
 * @property-read string                    $dashboardUrl
 * @property-read list<CampaignAttachment>  $attachments
 * @property-read int|null                  $templateId the campaign's own copy of the template, not the id you passed
 * @property-read int|null                  $senderId
 * @property-read string|null               $senderEmail
 * @property-read string|null               $senderName
 * @property-read list<ListRef>             $lists
 * @property-read CampaignUtm|null          $utm
 * @property-read \DateTimeImmutable|null   $scheduledAt
 * @property-read \DateTimeImmutable|null   $startedAt
 * @property-read \DateTimeImmutable|null   $finishedAt
 * @property-read \DateTimeImmutable|null   $createdAt
 * @property-read \DateTimeImmutable|null   $updatedAt
 * @property-read CampaignStats|null        $stats
 * @property-read CampaignAbTest|null       $abTest
 * @property-read CampaignFollowUp|null     $followUp
 * @property-read CampaignParent|null       $parent
 * @property-read CampaignStats|null        $totalStats aggregated statistics of the campaign and its parts
 */
final class CampaignDetails extends ApiObject
{
    protected const NESTED = [
        'attachments' => [CampaignAttachment::class],
        'lists' => [ListRef::class],
        'utm' => CampaignUtm::class,
        'stats' => CampaignStats::class,
        'abTest' => CampaignAbTest::class,
        'followUp' => CampaignFollowUp::class,
        'parent' => CampaignParent::class,
        'totalStats' => CampaignStats::class,
    ];
}
