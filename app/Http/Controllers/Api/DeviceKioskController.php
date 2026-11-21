<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceHeartbeat;
use App\Models\Personnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceKioskController extends Controller
{
    /**
     * Fast LAN probe so the Pi can find this laptop after a Wi-Fi IP change.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'smartdtr',
            'app' => 'INHS SMART-DTR',
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        DeviceHeartbeat::recordPing($request->ip());

        $device = DeviceHeartbeat::firstOrCreate(['device_name' => 'raspberry-pi-kiosk']);

        return response()->json([
            'ok' => true,
            'server_time' => now()->toDateTimeString(),
            'device' => [
                'status' => $device->statusLabel(),
                'scans_today' => $device->scan_count_date?->isToday() ? $device->scan_count_today : 0,
            ],
        ]);
    }

    /**
     * Personnel id + name for LCD overlay (folders in known_faces are personnel ids).
     */
    public function roster(): JsonResponse
    {
        $people = Personnel::active()
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number', 'role']);

        return response()->json([
            'data' => $people,
        ]);
    }

    /**
     * Face photo catalog for the Pi kiosk to download.
     */
    public function faces(): JsonResponse
    {
        $people = Personnel::active()->orderBy('name')->get();
        $data = [];

        foreach ($people as $person) {
            $dir = (string) $person->id;
            $files = [];

            if (Storage::disk('faces')->exists($dir)) {
                foreach (Storage::disk('faces')->files($dir) as $path) {
                    $name = basename($path);
                    if (! preg_match('/\.(jpe?g|png)$/i', $name)) {
                        continue;
                    }

                    $files[] = [
                        'name' => $name,
                        'size' => Storage::disk('faces')->size($path),
                    ];
                }
            }

            if ($files === []) {
                continue;
            }

            $data[] = [
                'id' => $person->id,
                'name' => $person->name,
                'files' => $files,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function faceFile(Request $request, int $id): StreamedResponse
    {
        Personnel::active()->findOrFail($id);

        $filename = basename((string) $request->query('file', ''));
        if ($filename === '' || ! preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png)$/i', $filename)) {
            abort(404);
        }

        $path = $id.'/'.$filename;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('faces');
        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path);
    }
}
