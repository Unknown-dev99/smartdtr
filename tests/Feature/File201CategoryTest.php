<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Personnel;
use App\Services\File201Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class File201CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_201_category_for_every_employee(): void
    {
        Storage::fake('local');

        $person = Personnel::create([
            'name' => 'Aiza T Donor',
            'role' => 'TCH II',
            'employee_number' => '00-029',
            'is_active' => true,
        ]);
        $admin = AdminUser::create([
            'username' => 'files-hr',
            'email' => 'files-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.files201.index'))
            ->assertOk()
            ->assertSee('Add category');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.files201.categories.store'), [
                'label' => 'Barangay clearance',
                'personnel_id' => $person->id,
            ])
            ->assertRedirect(route('admin.files201.show', ['personnel' => $person, 'open' => 'barangay_clearance']));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.files201.show', $person))
            ->assertOk()
            ->assertSee('Barangay clearance');

        $service = app(File201Service::class);
        $this->assertArrayHasKey('barangay_clearance', $service->categoriesFor($person));

        $saved = $service->store($person, 'barangay_clearance', UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'));
        $this->assertSame('Barangay clearance', $saved->categoryLabel());

        $this->actingAs($admin, 'admin')
            ->from(route('admin.files201.index'))
            ->post(route('admin.files201.categories.store'), ['label' => 'Appointments'])
            ->assertRedirect(route('admin.files201.index'))
            ->assertSessionHasErrors('label');
    }
}
