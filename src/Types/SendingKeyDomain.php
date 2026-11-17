<?php

declare(strict_types=1);

namespace RuSender\Types;

/**
 * The domain of a sending key.
 *
 * @property-read int     $id
 * @property-read string  $name
 * @property-read bool    $isVerified
 */
final class SendingKeyDomain extends ApiObject {}
