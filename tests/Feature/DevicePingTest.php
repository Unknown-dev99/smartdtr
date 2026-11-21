<?php

namespace Tests\Feature;

use Tests\TestCase;

class DevicePingTest extends TestCase
{
    public function test_device_ping_identifies_smartdtr(): void
    {
        $this->getJson('/api/device/ping')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'service' => 'smartdtr',
                'app' => 'INHS SMART-DTR',
            ]);
    }
}
