<?php

namespace Tests\Feature;

use App\Models\PersonalDataSheet;
use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonalDataSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_fills_the_personal_data_sheet_and_prints_it(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Personal Data Sheet')
            ->assertSee('Revised 2026')
            ->assertSee('PhilSys Card Number')
            ->assertSee('Sex at birth');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.personal-data-sheet.store'), $this->payload())
            ->assertRedirect(route('staff.files201.index', ['personal_data_sheet' => 1]));

        $sheet = PersonalDataSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertSame($personnel->id, $sheet->personnel_id);
        $this->assertSame('ANGEL', $sheet->details['surname']);
        $this->assertSame('female', $sheet->details['sex']);
        $this->assertSame('Teacher III', $sheet->details['work'][0]['position']);

        $file = Personnel201File::query()->first();
        $this->assertSame('personal_data_sheet', $file->category);
        $this->assertSame('Personal Data Sheet CS Form 212.pdf', $file->original_name);
        Storage::disk('local')->assertExists($file->stored_path);

        $pdf = $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.personal-data-sheet.print'))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('PERSONAL DATA SHEET', $pdf);
        $this->assertStringContainsString('CS Form No. 212', $pdf);
        $this->assertStringContainsString('Revised 2026', $pdf);
        $this->assertStringContainsString('Page 1 of 4', $pdf);
        $this->assertStringContainsString('Page 4 of 4', $pdf);
        $this->assertStringContainsString('ANGEL', $pdf);
        $this->assertStringContainsString('GERMILYN', $pdf);
        $this->assertStringContainsString('Isabela National High School', $pdf);
        $this->assertStringContainsString('Teacher III', $pdf);
        $this->assertStringContainsString('I declare under oath', $pdf);
        $this->assertGreaterThanOrEqual(4, preg_match_all('/\/Type\s*\/Page(?!\s*s)/', $pdf));
    }

    public function test_saving_again_updates_the_same_sheet(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();
        $payload = $this->payload();

        $this->actingAs($staff, 'staff')->post(route('staff.files201.personal-data-sheet.store'), $payload);
        $payload['tin'] = '123-456-789';
        $this->actingAs($staff, 'staff')->post(route('staff.files201.personal-data-sheet.store'), $payload);

        $this->assertSame(1, PersonalDataSheet::query()->count());
        $this->assertSame(1, Personnel201File::query()->count());
        $this->assertSame('123-456-789', PersonalDataSheet::query()->first()->details['tin']);
        $this->assertSame($personnel->id, PersonalDataSheet::query()->first()->personnel_id);
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
            'email' => 'germilyn.pds@example.test',
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
            'surname' => 'ANGEL',
            'first_name' => 'GERMILYN',
            'middle_name' => 'C',
            'name_extension' => '',
            'birth_date' => '1994-05-12',
            'place_of_birth' => 'City of Ilagan, Isabela',
            'sex' => 'female',
            'civil_status' => 'single',
            'height' => '1.58',
            'weight' => '52',
            'blood_type' => 'A+',
            'agency_employee_no' => '00-010',
            'citizenship' => 'filipino',
            'residential' => [
                'house' => '12', 'street' => 'Claravall St.', 'subdivision' => '',
                'barangay' => 'San Vicente', 'city' => 'City of Ilagan', 'province' => 'Isabela', 'zip' => '3300',
            ],
            'permanent' => [
                'house' => '12', 'street' => 'Claravall St.', 'subdivision' => '',
                'barangay' => 'San Vicente', 'city' => 'City of Ilagan', 'province' => 'Isabela', 'zip' => '3300',
            ],
            'mobile' => '09171234567',
            'email' => 'germilyn.angel@gmail.com',
            'education' => [
                ['level' => 'ELEMENTARY', 'school' => 'San Vicente Elementary School', 'course' => 'Elementary', 'from' => '2000', 'to' => '2006', 'units' => '', 'year_graduated' => '2006', 'honors' => ''],
                ['level' => 'SECONDARY', 'school' => 'Isabela National High School', 'course' => 'Secondary', 'from' => '2006', 'to' => '2010', 'units' => '', 'year_graduated' => '2010', 'honors' => ''],
                ['level' => 'COLLEGE', 'school' => 'Isabela State University', 'course' => 'Bachelor of Secondary Education', 'from' => '2010', 'to' => '2014', 'units' => '', 'year_graduated' => '2014', 'honors' => ''],
            ],
            'work' => [[
                'from' => '2016-06-01',
                'to' => 'Present',
                'position' => 'Teacher III',
                'office' => 'Isabela National High School',
                'salary' => '33843',
                'grade' => '13-1',
                'status' => 'Permanent',
                'government' => 'Y',
            ]],
            'q34a' => 'no',
            'q35a' => 'no',
            'q36' => 'no',
            'references' => [
                ['name' => 'Maria Santos', 'address' => 'City of Ilagan', 'contact' => '09170000000'],
            ],
            'gov_id' => 'PRC',
            'gov_id_no' => '1234567',
            'gov_id_issuance' => 'Tuguegarao City',
            'date_accomplished' => '2026-11-10',
        ];
    }
}
