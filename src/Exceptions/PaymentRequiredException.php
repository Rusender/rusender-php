<?php

declare(strict_types=1);

namespace RuSender\Exceptions;

/** 402: not enough balance or plan limit. */
final class PaymentRequiredException extends ApiException {}
