<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Personnel201File;
use App\Models\PositionDescriptionForm;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PositionDescriptionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_fills_the_position_description_form_and_prints_it(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.index'))
            ->assertOk()
            ->assertSee('Position Description Form')
            ->assertSee('DBM-CSC Form No. 1')
            ->assertSee('Qualification standards');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.files201.position-description.store'), $this->payload())
            ->assertRedirect(route('staff.files201.index', ['position_description' => 1]));

        $form = PositionDescriptionForm::query()->first();
        $this->assertNotNull($form);
        $this->assertSame($personnel->id, $form->personnel_id);
        $this->assertSame('ANGEL, GERMILYN C.', $form->employee_name);
        $this->assertSame('Teacher III', $form->details['position_title']);
        $this->assertSame('frequent', $form->details['contacts']['internal']['staff']);
        $this->assertTrue($form->details['work_office']);

        $file = Personnel201File::query()->first();
        $this->assertSame('position_description_forms', $file->category);
        $this->assertSame('Position Description Form.pdf', $file->original_name);
        Storage::disk('local')->assertExists($file->stored_path);

        $pdf = $this->actingAs($staff, 'staff')
            ->get(route('staff.files201.position-description.print'))
            ->assertOk()
            ->getContent();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('POSITION DESCRIPTION FORM', $pdf);
        $this->assertStringContainsString('DBM-CSC Form No. 1', $pdf);
        $this->assertStringContainsString('Revised Version No. 1', $pdf);
        $this->assertStringContainsString('Teacher III', $pdf);
        $this->assertStringContainsString('OSEC-DECSB-TCH3-000010-2016', $pdf);
        $this->assertStringContainsString('Department of Education', $pdf);
        $this->assertStringContainsString('Isabela National High School', $pdf);
        $this->assertStringContainsString('Percentage of Working Time', $pdf);
        $this->assertStringContainsString('Prepares lesson plans', $pdf);
        $this->assertStringContainsString("Employee's Name, Date and Signature", $pdf);
        $this->assertStringContainsString('I have received a copy of this position description', $pdf);
        $pages = preg_match_all('/\/Type\s*\/Page(?!\s*s)/', $pdf);
        $this->assertGreaterThanOrEqual(1, $pages);
        $this->assertLessThanOrEqual(2, $pages);
    }

    public function test_saving_again_updates_the_same_form(): void
    {
        Storage::fake('local');
        [$personnel, $staff] = $this->employee();
        $payload = $this->payload();

        $this->actingAs($staff, 'staff')->post(route('staff.files201.position-description.store'), $payload);
        $payload['salary_grade'] = 'SG-13';
        $this->actingAs($staff, 'staff')->post(route('staff.files201.position-description.store'), $payload);

        $this->assertSame(1, PositionDescriptionForm::query()->count());
        $this->assertSame(1, Personnel201File::query()->count());
        $this->assertSame('SG-13', PositionDescriptionForm::query()->first()->details['salary_grade']);
        $this->assertSame($personnel->id, PositionDescriptionForm::query()->first()->personnel_id);
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
            'email' => 'germilyn.pdf@example.test',
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
            'position_title' => 'Teacher III',
            'item_number' => 'OSEC-DECSB-TCH3-000010-2016',
            'salary_grade' => 'SG-13',
            'lg_unit' => '',
            'lg_class' => '',
            'department' => 'Department of Education',
            'bureau' => 'Region II',
            'division' => 'Schools Division of the City of Ilagan',
            'workstation' => 'Isabela National High School',
            'present_approp' => 'GAA FY 2026',
            'previous_approp' => '',
            'salary_authorized' => 'P 33,843.00',
            'other_compensation' => 'PERA',
            'immediate_supervisor' => 'School Head',
            'next_supervisor' => 'Schools Division Superintendent',
            'supervised' => [
                ['title' => '', 'item' => ''],
            ],
            'machines' => 'Computer and printer',
            'contacts' => [
                'internal' => [
                    'executive' => 'occasional',
                    'supervisors' => 'frequent',
                    'non_supervisors' => '',
                    'staff' => 'frequent',
                ],
                'external' => [
                    'general_public' => 'occasional',
                    'other_agencies' => '',
                    'others' => '',
                ],
            ],
            'others_specify' => '',
            'work_office' => '1',
            'work_field' => '1',
            'unit_function' => 'Provides instruction in the Senior High School.',
            'job_summary' => 'Teaches assigned learning areas and keeps class records.',
            'education' => "Bachelor's degree in education",
            'experience' => '1 year of relevant teaching experience',
            'training' => 'None required',
            'eligibility' => 'RA 1080 (Teacher)',
            'core_competencies' => 'Self-management',
            'core_level' => 'Intermediate',
            'leadership_competencies' => '',
            'leadership_level' => '',
            'duties' => [
                ['percent' => '70%', 'duty' => 'Prepares lesson plans and teaches assigned subjects'],
                ['percent' => '30%', 'duty' => 'Keeps class records and prepares reports'],
            ],
            'duties_level' => 'Intermediate',
            'employee_name' => 'ANGEL, GERMILYN C.',
            'employee_date' => '2026-11-10',
            'supervisor_name' => 'School Head',
            'supervisor_date' => '2026-11-10',
        ];
    }
}
