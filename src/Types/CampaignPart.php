<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * An A/B test variant or a follow-up in a campaign list.
 *
 * @property-read int          $id
 * @property-read string       $role ab_variant or follow_up
 * @property-read string       $name
 * @property-read string|null  $subject
 * @property-read string|null  $previewTitle
 * @property-read string       $status
 */
final class CampaignPart extends ApiObject {}
