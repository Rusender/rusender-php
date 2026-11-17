<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** 401: the key is missing, invalid or revoked. */
final class AuthenticationException extends ApiException {}
