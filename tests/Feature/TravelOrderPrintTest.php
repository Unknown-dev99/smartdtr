<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\StaffUser;
use App\Models\TravelOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelOrderPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_print_an_approved_travel_order(): void
    {
        $staff = $this->employee('germilyn.travel@example.test', '00-201');
        $approved = $this->order($staff, 'approved');
        $pending = $this->order($staff, 'pending');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.travel-orders'))
            ->assertOk()
            ->assertSee('After HR approves a request, use Print')
            ->assertSee(route('staff.travel-orders.print', $approved), false)
            ->assertDontSee(route('staff.travel-orders.print', $pending), false);

        $download = $this->actingAs($staff, 'staff')
            ->get(route('staff.travel-orders.print', $approved));

        $download->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $download->headers->get('content-type')
        );
        $this->assertStringContainsString('.docx', (string) $download->headers->get('content-disposition'));
        $file = $download->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('PK', (string) file_get_contents($file));
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($file) === true);
        $this->assertStringContainsString('ANGEL', (string) $zip->getFromName('word/document.xml'));
        $zip->close();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.travel-orders.print', $pending))
            ->assertForbidden();
    }

    public function test_employee_cannot_print_someone_elses_travel_order(): void
    {
        $owner = $this->employee('owner.travel@example.test', '00-202');
        $other = $this->employee('other.travel@example.test', '00-203');
        $order = $this->order($owner, 'approved');

        $this->actingAs($other, 'staff')
            ->get(route('staff.travel-orders.print', $order))
            ->assertForbidden();
    }

    protected function employee(string $email, string $employeeNumber): StaffUser
    {
        $personnel = Personnel::create([
            'name' => 'Germilyn C Angel',
            'role' => 'TCH III',
            'employee_number' => $employeeNumber,
        ]);

        return StaffUser::create([
            'personnel_id' => $personnel->id,
            'name' => 'Germilyn C Angel',
            'last_name' => 'Angel',
            'first_name' => 'Germilyn',
            'middle_name' => 'C',
            'email' => $email,
            'password' => 'Password123!',
            'role' => 'teacher',
            'status' => 'active',
        ]);
    }

    protected function order(StaffUser $staff, string $status): TravelOrder
    {
        return TravelOrder::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $staff->personnel_id,
            'position_designation' => 'Teacher III',
            'permanent_station' => 'Isabela National High School',
            'purpose' => 'Division training',
            'host_of_activity' => 'Schools Division of the City of Ilagan',
            'travel_type' => 'within',
            'date_from' => '2026-11-16',
            'date_to' => '2026-11-17',
            'destination' => 'City of Ilagan',
            'fund_source' => 'MOOE',
            'status' => $status,
            'reviewed_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
