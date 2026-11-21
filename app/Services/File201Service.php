<?php

namespace App\Services;

use App\Models\File201Category;
use App\Models\Personnel;
use App\Models\Personnel201File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class File201Service
{
    public function categories(): array
    {
        $custom = File201Category::query()->orderBy('id')->pluck('label', 'slug')->all();

        return config('smartdtr.file_201_categories', []) + $custom;
    }

    public function label(string $category): string
    {
        return $this->categories()[$category] ?? $category;
    }

    public function addCategory(string $label): File201Category
    {
        $label = trim((string) preg_replace('/\s+/', ' ', $label));
        $slug = substr(Str::slug($label, '_'), 0, 80);
        $taken = $slug === '' || array_key_exists($slug, $this->categories());

        foreach ($this->categories() as $existing) {
            if (strcasecmp($existing, $label) === 0) {
                $taken = true;
            }
        }

        if ($label === '' || $taken) {
            throw ValidationException::withMessages([
                'label' => 'Enter a category name that is not already on the 201 file.',
            ]);
        }

        return File201Category::create([
            'slug' => $slug,
            'label' => $label,
        ]);
    }

    /**
     * Compensatory Time Off is a non-teaching record. Teaching staff keep the
     * Updated Service Record and do not see the CTO folder.
     */
    public function categoriesFor(?Personnel $personnel): array
    {
        $categories = $this->categories();

        if (! $personnel?->isNonTeaching()) {
            unset($categories['compensatory_time_off']);

            return $categories;
        }

        $first = array_intersect_key($categories, array_flip([
            'updated_service_record',
            'compensatory_time_off',
        ]));

        return $first + $categories;
    }

    public function categoryKeys(): array
    {
        return array_keys($this->categories());
    }

    public function categoryRule(): array
    {
        return ['required', 'string', Rule::in($this->categoryKeys())];
    }

    public function uploadRules(): array
    {
        return [
            'category' => $this->categoryRule(),
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function store(Personnel $personnel, string $category, UploadedFile $file, ?string $notes = null): Personnel201File
    {
        if (! array_key_exists($category, $this->categoriesFor($personnel))) {
            throw new InvalidArgumentException('Invalid 201 file category.');
        }

        $path = $file->store('201_files/'.$personnel->id.'/'.$category, 'local');

        [$actorType, $actorId, $actorName] = $this->resolveUploader();

        return Personnel201File::create([
            'personnel_id' => $personnel->id,
            'category' => $category,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'notes' => $notes,
            'uploaded_by_type' => $actorType,
            'uploaded_by_id' => $actorId,
            'uploaded_by_name' => $actorName,
        ]);
    }

    /**
     * @return array{0:?string,1:?int,2:?string}
     */
    protected function resolveUploader(): array
    {
        if ($admin = Auth::guard('admin')->user()) {
            return ['admin', $admin->id, $admin->username];
        }

        if ($staff = Auth::guard('staff')->user()) {
            return ['staff', $staff->id, $staff->name];
        }

        return [null, null, null];
    }
}
