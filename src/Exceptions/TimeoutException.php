<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** No response within the configured timeout. */
final class TimeoutException extends NetworkException {}
