<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of adding campaigns to a folder or removing them.
 *
 * @property-read int  $folderId
 * @property-read int  $affectedCampaigns
 */
final class FolderCampaignsResult extends ApiObject {}
