<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of archiving or unarchiving a campaign.
 *
 * @property-read bool       $archived
 * @property-read list<int>  $campaignIds
 */
final class ArchiveResult extends ApiObject {}
