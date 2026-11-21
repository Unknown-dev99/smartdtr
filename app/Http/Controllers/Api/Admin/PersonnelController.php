<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonnelController extends Controller
{
    public function index(): JsonResponse
    {
        $personnel = Personnel::orderBy('name')->get()->map(function (Personnel $p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'role' => $p->role,
                'employee_number' => $p->employee_number,
                'official_hours_regular' => $p->official_hours_regular,
                'official_hours_saturday' => $p->official_hours_saturday,
                'face_sample_count' => $p->faceSampleCount(),
                'face_dataset_dir' => $p->face_dataset_dir,
            ];
        });

        return response()->json(['data' => $personnel]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'employee_number' => 'nullable|string|max:50',
            'official_hours_regular' => 'nullable|string|max:255',
            'official_hours_saturday' => 'nullable|string|max:255',
        ]);

        $personnel = Personnel::create($validated);
        $faceDir = storage_path('app/known_faces/'.$personnel->id);
        if (! is_dir($faceDir)) {
            mkdir($faceDir, 0755, true);
        }
        $personnel->update(['face_dataset_dir' => $faceDir]);

        return response()->json([
            'message' => 'Personnel created',
            'data' => $personnel->fresh(),
        ], 201);
    }

    public function uploadFaces(Request $request, int $id): JsonResponse
    {
        $personnel = Personnel::findOrFail($id);

        $request->validate([
            'images' => 'nullable|array|min:1|max:10',
            'images.*' => 'file|mimes:jpeg,jpg,png|max:5120',
            'images_base64' => 'nullable|array|min:1|max:10',
            'images_base64.*' => 'string',
        ]);

        $dir = (string) $personnel->id;
        Storage::disk('faces')->makeDirectory($dir);
        $saved = 0;

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'_'.time().'_'.$saved.'.'.$file->getClientOriginalExtension();
                $file->storeAs($dir, $filename, 'faces');
                $saved++;
            }
        }

        if ($request->filled('images_base64')) {
            foreach ($request->input('images_base64') as $index => $base64) {
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
                    $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                    $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
                } else {
                    $ext = 'jpg';
                    $data = base64_decode($base64);
                }

                if ($data) {
                    $filename = 'face_'.time()."_{$index}.{$ext}";
                    Storage::disk('faces')->put("{$dir}/{$filename}", $data);
                    $saved++;
                }
            }
        }

        $facePath = storage_path('app/known_faces/'.$personnel->id);
        $personnel->update(['face_dataset_dir' => $facePath]);

        $total = $personnel->fresh()->faceSampleCount();

        return response()->json([
            'message' => 'Face images uploaded. Rebuild Pi encodings after upload.',
            'saved' => $saved,
            'face_sample_count' => $total,
        ]);
    }
}
