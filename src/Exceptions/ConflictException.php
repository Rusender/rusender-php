<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** 409: a state conflict, or the Idempotency-Key was reused with a different body. */
final class ConflictException extends ApiException {}
