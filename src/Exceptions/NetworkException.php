<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** The request did not reach the API or no response was received: DNS, connection, TLS. */
class NetworkException extends RuSenderException {}
