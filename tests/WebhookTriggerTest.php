<?php

declare(strict_types=1);

namespace RuSender\Tests;

use RuSender\Tests\Support\TestCase;
use RuSender\Types\WebhookTrigger;

final class WebhookTriggerTest extends TestCase
{
    public function testConstantNamesMatchTheirValues(): void
    {
        $constants = (new \ReflectionClass(WebhookTrigger::class))->getConstants();
        self::assertCount(33, $constants);
        foreach ($constants as $name => $value) {
            self::assertSame(strtoupper(str_replace('.', '_', (string) $value)), $name);
        }
        self::assertSame('sending_key.state_changed', WebhookTrigger::SENDING_KEY_STATE_CHANGED);
    }
}
