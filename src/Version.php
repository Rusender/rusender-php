<?php

declare(strict_types=1);

namespace RuSender;

/**
 * The SDK version, sent in the User-Agent header. CI tags the release `v` + VERSION: bump it in the merge request
 * that changes the package.
 */
final class Version
{
    public const VERSION = '0.1.0';
}
