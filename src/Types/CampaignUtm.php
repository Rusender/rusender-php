<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * UTM tags added to campaign links.
 *
 * @property-read string|null  $utm_source
 * @property-read string|null  $utm_medium
 * @property-read string|null  $utm_campaign
 */
final class CampaignUtm extends ApiObject {}
