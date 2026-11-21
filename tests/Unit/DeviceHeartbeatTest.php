<?php

namespace Tests\Unit;

use App\Models\DeviceHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_is_online_when_heartbeat_is_fresh(): void
    {
        $device = DeviceHeartbeat::create([
            'device_name' => 'raspberry-pi-kiosk',
            'last_seen_at' => now()->subSeconds(20),
        ]);

        $this->assertTrue($device->isOnline());
        $this->assertSame('Online', $device->statusLabel());
    }

    public function test_kiosk_is_offline_when_heartbeat_is_stale(): void
    {
        $device = DeviceHeartbeat::create([
            'device_name' => 'raspberry-pi-kiosk',
            'last_seen_at' => now()->subSeconds(90),
        ]);

        $this->assertFalse($device->isOnline());
        $this->assertSame('Offline', $device->statusLabel());
    }
}
