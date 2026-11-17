<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** 400 and 422: the request failed validation; fields are in details. */
final class ValidationException extends ApiException {}
