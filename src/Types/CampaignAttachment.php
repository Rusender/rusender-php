<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * A file attached to a campaign.
 *
 * @property-read int     $id
 * @property-read string  $fileName
 * @property-read int     $size
 */
final class CampaignAttachment extends ApiObject {}
