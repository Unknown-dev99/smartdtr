<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\StaffUser;
use App\Models\WorkExperienceSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkExperienceSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_fills_the_work_experience_sheet_and_prints_it(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Work Experience Sheet')
            ->assertSee('Summary of Actual Duties')
            ->assertSee('Immediate Supervisor');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.work-experience.store'), $this->payload())
            ->assertRedirect(route('staff.files201.index', ['work_experience' => 1]));

        $sheet = WorkExperienceSheet::query()->first();
        $this->assertNotNull($sheet);
        $this->assertSame($personnel->id, $sheet->personnel_id);
        $this->assertSame('ANGEL GERMILYN C', $sheet->printed_name);
        $this->assertCount(2, $sheet->entries);
        $this->assertSame('February 2011 - Present', $sheet->entries[0]['duration']);

        $file = Personnel201File::query()->first();
        $this->assertSame('work_experience_sheet', $file->category);
        $this->assertSame('Work Experience Sheet.pdf', $file->original_name);
        Storage::disk('local')->assertExists($file->stored_path);

        $pdf = $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.work-experience.print'))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('WORK EXPERIENCE SHEET', $pdf);
        $this->assertStringContainsString('Instructions:', $pdf);
        $this->assertStringContainsString('1998-Present', $pdf);
        $this->assertStringContainsString('Sample: If applying to Supervising Administrative Officer', $pdf);
        $this->assertStringContainsString('Maria Estrada', $pdf);
        $this->assertStringContainsString('Teacher III', $pdf);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page(?!\s*s)/', $pdf));
        $this->assertStringContainsString('Duration: February 2011 - Present', $pdf);
        $this->assertStringContainsString('Name of Office/Unit: Senior High School', $pdf);
        $this->assertStringContainsString('List of Accomplishments and Contributions', $pdf);
        $this->assertStringContainsString('Summary of Actual Duties', $pdf);
        $this->assertStringContainsString('Attachment to CS Form No. 212', $pdf);
        $this->assertStringContainsString('Signature over Printed Name', $pdf);
        $this->assertStringContainsString('of Employee/Applicant', $pdf);
        $this->assertStringContainsString('Prepared lesson plans', $pdf);
    }

    public function test_saving_again_updates_the_same_sheet(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();
        $payload = $this->payload();

        $this->actingAs($staff, 'staff')->post(route('staff.files201.work-experience.store'), $payload);
        $payload['entries'][0]['position'] = 'Master Teacher I';
        $this->actingAs($staff, 'staff')->post(route('staff.files201.work-experience.store'), $payload);

        $this->assertSame(1, WorkExperienceSheet::query()->count());
        $this->assertSame(1, Personnel201File::query()->count());
        $this->assertSame('Master Teacher I', WorkExperienceSheet::query()->first()->entries[0]['position']);
        $this->assertSame($personnel->id, WorkExperienceSheet::query()->first()->personnel_id);
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
            'email' => 'germilyn.wes@example.test',
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
            'printed_name' => 'ANGEL GERMILYN C',
            'sheet_date' => '2026-11-10',
            'entries' => [
                [
                    'duration' => 'February 2011 - Present',
                    'position' => 'Teacher III',
                    'office_unit' => 'Senior High School',
                    'supervisor' => 'School Head',
                    'agency' => 'Isabela National High School, City of Ilagan, Isabela',
                    'accomplishments' => 'Prepared lesson plans',
                    'duties' => 'Teaches assigned subjects and records daily attendance.',
                ],
                [
                    'duration' => 'June 2008 - January 2011',
                    'position' => 'Teacher I',
                    'office_unit' => 'Junior High School',
                    'supervisor' => 'Department Head',
                    'agency' => 'Isabela National High School, City of Ilagan, Isabela',
                    'accomplishments' => '',
                    'duties' => 'Handled junior high classes.',
                ],
            ],
        ];
    }
}
