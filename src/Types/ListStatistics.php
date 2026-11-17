<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * List statistics; rates are shares from 0 to 1.
 *
 * @property-read int    $listId
 * @property-read int    $countActiveContacts
 * @property-read int    $countNotActiveContacts
 * @property-read int    $countContacts
 * @property-read int    $countUnsubscribe
 * @property-read int    $countSending
 * @property-read int    $countOpen
 * @property-read int    $countError
 * @property-read int    $countComplaint
 * @property-read float  $openRate
 * @property-read float  $errorRate
 * @property-read float  $unsubscribeRate
 * @property-read float  $complaintRate
 */
final class ListStatistics extends ApiObject {}
