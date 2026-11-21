<?php

namespace Tests\Feature;

use App\Models\MedicalCertificate;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_fills_the_medical_certificate_and_prints_it(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Medical Certificate')
            ->assertSee('CS Form No. 211')
            ->assertSee('Revised 2025')
            ->assertSee('Neuro-Psychiatric Examination');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.medical-certificate.store'), $this->payload())
            ->assertRedirect(route('staff.files201.index', ['medical_certificate' => 1]));

        $certificate = MedicalCertificate::query()->first();
        $this->assertNotNull($certificate);
        $this->assertSame($personnel->id, $certificate->personnel_id);
        $this->assertSame('ANGEL, GERMILYN C.', $certificate->appointee_name);
        $this->assertSame('fit', $certificate->details['fitness']);
        $this->assertContains('urinalysis', $certificate->details['exams']);

        $file = Personnel201File::query()->first();
        $this->assertSame('medical_certificate_csc211', $file->category);
        $this->assertSame('Medical Certificate CS Form 211.pdf', $file->original_name);
        Storage::disk('local')->assertExists($file->stored_path);

        $pdf = $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.medical-certificate.print'))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('CS Form No. 211', $pdf);
        $this->assertStringContainsString('Revised 2025', $pdf);
        $this->assertStringContainsString('MEDICAL CERTIFICATE', $pdf);
        $this->assertStringContainsString('For Employment', $pdf);
        $this->assertStringContainsString('ANGEL, GERMILYN C.', $pdf);
        $this->assertStringContainsString('Isabela National High School', $pdf);
        $this->assertStringContainsString('Teacher III', $pdf);
        $this->assertStringContainsString('Urinalysis', $pdf);
        $this->assertStringContainsString('Neuro-Psychiatric Examination', $pdf);
        $this->assertStringContainsString('FIT', $pdf);
        $this->assertStringContainsString('UNFIT', $pdf);
        $this->assertStringContainsString('SIGNATURE over PRINTED NAME', $pdf);
        $this->assertStringContainsString('DATE EXAMINED', $pdf);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page(?!\s*s)/', $pdf));
    }

    public function test_saving_again_updates_the_same_certificate(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();
        $payload = $this->payload();

        $this->actingAs($staff, 'staff')->post(route('staff.files201.medical-certificate.store'), $payload);
        $payload['blood_type'] = 'O+';
        $this->actingAs($staff, 'staff')->post(route('staff.files201.medical-certificate.store'), $payload);

        $this->assertSame(1, MedicalCertificate::query()->count());
        $this->assertSame(1, Personnel201File::query()->count());
        $this->assertSame('O+', MedicalCertificate::query()->first()->details['blood_type']);
        $this->assertSame($personnel->id, MedicalCertificate::query()->first()->personnel_id);
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
            'email' => 'germilyn.med@example.test',
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
            'appointee_name' => 'ANGEL, GERMILYN C.',
            'address' => 'San Vicente, City of Ilagan, Isabela',
            'age' => '32',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'agency_address' => 'Isabela National High School, Claravall St., San Vicente, City of Ilagan, Isabela',
            'proposed_position' => 'Teacher III',
            'exams' => ['blood_test', 'urinalysis', 'chest_xray'],
            'fitness' => 'fit',
            'physician_name' => 'DR. MARIA SANTOS',
            'other_information' => 'No abnormal findings.',
            'physician_agency' => 'City Health Office, City of Ilagan',
            'license_no' => '0123456',
            'height' => '1.58',
            'weight' => '52',
            'blood_type' => 'A+',
            'designation' => 'Medical Officer III',
            'date_examined' => '2026-11-10',
        ];
    }
}
