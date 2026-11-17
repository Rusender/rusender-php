<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of contacts->removeFromAllLists().
 *
 * @property-read int  $affectedContacts
 * @property-read int  $removedLinks
 */
final class RemoveFromAllListsResult extends ApiObject {}
