<?php

namespace Tests\Unit;

use App\Models\Personnel;
use Tests\TestCase;

class PersonnelFaceSampleCountTest extends TestCase
{
    public function test_counts_jpeg_and_png_files_in_the_dataset_folder(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'smartdtr-faces-'.bin2hex(random_bytes(4));
        mkdir($dir);

        try {
            file_put_contents($dir.DIRECTORY_SEPARATOR.'one.jpg', 'x');
            file_put_contents($dir.DIRECTORY_SEPARATOR.'two.JPEG', 'x');
            file_put_contents($dir.DIRECTORY_SEPARATOR.'three.png', 'x');
            file_put_contents($dir.DIRECTORY_SEPARATOR.'ignore.txt', 'x');
            mkdir($dir.DIRECTORY_SEPARATOR.'nested');

            $personnel = new Personnel([
                'face_dataset_dir' => $dir,
            ]);

            $this->assertSame(3, $personnel->faceSampleCount());
        } finally {
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                if (is_file($path)) {
                    unlink($path);
                } elseif (is_dir($path)) {
                    rmdir($path);
                }
            }
            rmdir($dir);
        }
    }

    public function test_returns_zero_when_the_folder_is_missing(): void
    {
        $personnel = new Personnel([
            'face_dataset_dir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'smartdtr-missing-faces',
        ]);

        $this->assertSame(0, $personnel->faceSampleCount());
    }
}
