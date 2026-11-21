<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_saves_a_yearly_clearance_and_prints_the_csc_form(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('CSC Request for Clearance')
            ->assertSee('Lastname')
            ->assertSee('Present Position');

        $payload = $this->payload();

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.clearance.store'), $payload)
            ->assertRedirect(route('staff.files201.index', ['clearance_year' => 2026]));

        $this->assertSame(1, ClearanceRequest::query()->count());
        $saved = ClearanceRequest::query()->first();
        $this->assertSame('ANGEL', $saved->last_name);
        $this->assertSame('promotion', $saved->purpose);

        $file = Personnel201File::query()->first();
        $this->assertSame('clearance_property_money_csc7', $file->category);
        $this->assertSame('Request for Clearance 2026.pdf', $file->original_name);
        Storage::disk('local')->assertExists($file->stored_path);

        $pdf = $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.clearance.print', $saved))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('Request for Clearance', $pdf);
        $this->assertStringContainsString('ANGEL', $pdf);
        $this->assertStringContainsString('To be filled up by CSC Personnel', $pdf);
        $this->assertStringContainsString('DATA PRIVACY NOTICE', $pdf);
    }

    public function test_saving_the_same_year_updates_the_clearance(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();
        $payload = $this->payload();

        $this->actingAs($staff, 'staff')->post(route('staff.files201.clearance.store'), $payload);
        $payload['purpose'] = 'retirement';
        $payload['home_address'] = 'San Vicente, City of Ilagan';
        $this->actingAs($staff, 'staff')->post(route('staff.files201.clearance.store'), $payload);

        $this->assertSame(1, ClearanceRequest::query()->count());
        $this->assertSame(1, Personnel201File::query()->count());
        $this->assertSame('retirement', ClearanceRequest::query()->first()->purpose);
        $this->assertSame($personnel->id, ClearanceRequest::query()->first()->personnel_id);
    }

    /**
     * @return array{0:Personnel,1:StaffUser}
     */
    protected function employee(): array
    {
        $personnel = Personnel::create([
            'name' => 'Germilyn C Angel',
            'role' => 'TCH III',
            'employee_number' => '00-010',
        ]);
        $staff = StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Germilyn C Angel',
            'last_name' => 'Angel',
            'first_name' => 'Germilyn',
            'middle_name' => 'C',
            'email' => 'germilyn.angel@example.test',
            'phone' => '09171234567',
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        return [$personnel, $staff];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'year' => 2026,
            'request_date' => '2026-11-10',
            'last_name' => 'ANGEL',
            'first_name' => 'GERMILYN',
            'middle_initial' => 'C',
            'home_address' => 'City of Ilagan, Isabela',
            'contact_no' => '09171234567',
            'email' => 'germilyn.angel@example.test',
            'present_position' => 'TCH III',
            'agency_name' => 'Isabela National High School',
            'agency_address' => 'Claravall St., San Vicente, City of Ilagan, Isabela',
            'purpose' => 'promotion',
        ];
    }
}
