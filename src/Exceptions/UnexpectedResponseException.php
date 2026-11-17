<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/**
 * A response outside the API contract, such as an HTML page of a proxy with status 200 or a list without items.
 * It is never returned as empty data.
 */
final class UnexpectedResponseException extends RuSenderException {}
