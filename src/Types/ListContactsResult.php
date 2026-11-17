<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The result of adding contacts to a list or removing them.
 *
 * @property-read int  $listId
 * @property-read int  $affectedContacts
 */
final class ListContactsResult extends ApiObject {}
