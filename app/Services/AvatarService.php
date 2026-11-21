<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class AvatarService
{
    public function store(Model $user, string $guard, UploadedFile $file): string
    {
        $this->deleteFile($user);

        $directory = public_path("avatars/{$guard}");
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $filename = $user->getKey().'.'.$extension;
        $relativePath = "avatars/{$guard}/{$filename}";

        $file->move($directory, $filename);

        $user->update(['avatar_path' => $relativePath]);

        return $relativePath;
    }

    public function remove(Model $user): void
    {
        $this->deleteFile($user);
        $user->update(['avatar_path' => null]);
    }

    protected function deleteFile(Model $user): void
    {
        if (! $user->avatar_path) {
            return;
        }

        $fullPath = public_path($user->avatar_path);
        if (is_file($fullPath)) {
            File::delete($fullPath);
        }
    }
}
