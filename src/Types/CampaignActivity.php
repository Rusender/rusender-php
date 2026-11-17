<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * Opens and clicks of a campaign on one day.
 *
 * @property-read string  $date the day as DD.MM.YYYY
 * @property-read int     $opens
 * @property-read int     $clicks
 */
final class CampaignActivity extends ApiObject {}
