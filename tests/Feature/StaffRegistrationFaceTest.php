<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\StaffUser;
use App\Services\PythonFaceEnrollmentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class StaffRegistrationFaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_includes_face_capture(): void
    {
        $this->get(route('staff.register'))
            ->assertOk()
            ->assertSee('Facial recognition')
            ->assertSee('Blink three times')
            ->assertSee('Start Camera')
            ->assertSee('Capture your face to register');
    }

    public function test_registration_requires_three_face_captures(): void
    {
        $this->post(route('staff.register'), $this->account())
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('staff_users', 0);
    }

    public function test_registration_saves_face_samples_until_hr_approves(): void
    {
        Storage::fake('faces');
        $this->mockValidator();

        $this->post(route('staff.register'), $this->account([
            'images' => $this->captures(),
            'blink_verifications' => ['1', '1', '1'],
        ]))->assertRedirect(route('staff.login'));

        $staff = StaffUser::query()->where('email', 'juan.face@example.test')->first();
        $this->assertNotNull($staff);
        $this->assertSame('pending', $staff->status);
        $this->assertNotNull($staff->personnel_id);

        $personnel = Personnel::find($staff->personnel_id);
        $this->assertFalse($personnel->is_active);
        $this->assertCount(3, Storage::disk('faces')->files((string) $personnel->id));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'face.registered_with_account',
            'subject_id' => $personnel->id,
        ]);

        $admin = AdminUser::create([
            'username' => 'face-hr',
            'email' => 'face-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.staff.activate', $staff))
            ->assertRedirect();

        $personnel->refresh();
        $staff->refresh();
        $this->assertSame('active', $staff->status);
        $this->assertTrue($personnel->is_active);
        $this->assertCount(3, Storage::disk('faces')->files((string) $personnel->id));
    }

    public function test_rejecting_registration_removes_the_pending_face_samples(): void
    {
        Storage::fake('faces');
        $this->mockValidator();

        $this->post(route('staff.register'), $this->account([
            'email' => 'rejected.face@example.test',
            'images' => $this->captures(),
            'blink_verifications' => ['1', '1', '1'],
        ]));

        $staff = StaffUser::query()->where('email', 'rejected.face@example.test')->firstOrFail();
        $personnelId = $staff->personnel_id;

        $admin = AdminUser::create([
            'username' => 'face-hr',
            'email' => 'face-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.hr.staff.reject', $staff))
            ->assertRedirect();

        $this->assertNull($staff->fresh()->personnel_id);
        $this->assertNull(Personnel::find($personnelId));
        $this->assertSame([], Storage::disk('faces')->allFiles());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function account(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Perez',
            'email' => 'juan.face@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'department' => 'Science',
        ], $overrides);
    }

    /**
     * @return array<int, UploadedFile>
     */
    protected function captures(): array
    {
        return [
            UploadedFile::fake()->image('capture-1.jpg', 640, 480),
            UploadedFile::fake()->image('capture-2.jpg', 640, 480),
            UploadedFile::fake()->image('capture-3.jpg', 640, 480),
        ];
    }

    protected function mockValidator(): void
    {
        $this->mock(PythonFaceEnrollmentValidator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('validate')->once()->andReturn([
                'ok' => true,
                'message' => 'Three compatible face encodings verified.',
                'encodings' => 3,
                'max_distance' => 0.28,
            ]);
        });
    }
}
