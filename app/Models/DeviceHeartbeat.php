<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DeviceHeartbeat extends Model
{
    protected $fillable = [
        'device_name', 'last_seen_at', 'last_scan_at',
        'ip_address', 'scan_count_today', 'scan_count_date',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_scan_at' => 'datetime',
            'scan_count_date' => 'date',
        ];
    }

    /**
     * Pi posts a heartbeat about every 20 seconds. Two missed beats = offline.
     */
    public const ONLINE_SECONDS = 45;

    public function isOnline(?int $seconds = null): bool
    {
        $seconds ??= self::ONLINE_SECONDS;

        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->gte(now()->subSeconds($seconds));
    }

    public function statusLabel(): string
    {
        if (! $this->last_seen_at) {
            return 'Never connected';
        }

        return $this->isOnline() ? 'Online' : 'Offline';
    }

    public function lastSeenHuman(): string
    {
        return $this->last_seen_at
            ? $this->last_seen_at->diffForHumans()
            : 'Never';
    }

    /**
     * @return array{online:bool,status:string,last_seen:string,last_scan:?string,scans_today:int}
     */
    public function dashboardStatus(): array
    {
        return [
            'online' => $this->isOnline(),
            'status' => $this->statusLabel(),
            'last_seen' => $this->lastSeenHuman(),
            'last_scan' => $this->last_scan_at?->diffForHumans(),
            'scans_today' => $this->scan_count_date?->isToday() ? (int) $this->scan_count_today : 0,
        ];
    }

    public static function recordScan(?string $ip = null): void
    {
        $today = now()->toDateString();
        $device = static::firstOrCreate(['device_name' => 'raspberry-pi-kiosk']);

        $scanCount = ($device->scan_count_date?->toDateString() === $today)
            ? $device->scan_count_today + 1
            : 1;

        $device->update([
            'last_seen_at' => now(),
            'last_scan_at' => now(),
            'ip_address' => $ip,
            'scan_count_today' => $scanCount,
            'scan_count_date' => $today,
        ]);
    }

    public static function recordPing(?string $ip = null): void
    {
        $device = static::firstOrCreate(['device_name' => 'raspberry-pi-kiosk']);
        $device->update([
            'last_seen_at' => now(),
            'ip_address' => $ip ?? $device->ip_address,
        ]);
    }
}
