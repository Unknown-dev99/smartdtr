<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\LeaveCard;
use App\Models\LeaveRequest;
use App\Models\Personnel;
use App\Models\StaffUser;
use App\Services\LeaveBalanceService;
use App\Services\LeaveCardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('leave_requests') && ! Schema::hasTable('leave_requests_data')) {
            Schema::rename('leave_requests', 'leave_requests_data');
        }
    }

    public function test_partial_first_month_then_full_months_and_vacation_leave_deducts_only_vl(): void
    {
        $this->assertSame(0.708, app(LeaveCardService::class)->creditForDays(17));

        $person = Personnel::create([
            'name' => 'Christopher Beyer',
            'role' => 'ADA-1',
            'employee_number' => '00-610',
            'is_active' => true,
        ]);
        $card = LeaveCard::create([
            'personnel_id' => $person->id,
            'entrance_to_duty' => '2024-02-19',
        ]);

        $service = app(LeaveCardService::class);
        $february = $this->month($service->months($person, $card, Carbon::parse('2024-03-31')), '2024-02');
        $march = $this->month($service->months($person, $card, Carbon::parse('2024-03-31')), '2024-03');

        $this->assertSame(1.25, $february['earned']);
        $this->assertSame(1.25, $february['vl_balance']);
        $this->assertSame(1.25, $march['earned']);
        $this->assertSame(2.5, $march['vl_balance']);
        $this->assertSame(2.5, $march['sl_balance']);

        $staff = StaffUser::create([
            'personnel_id' => $person->id,
            'name' => 'Christopher Beyer',
            'last_name' => 'Beyer',
            'first_name' => 'Christopher',
            'email' => 'christopher.beyer@example.test',
            'password' => 'Password123!',
            'role' => 'non_teaching',
            'status' => 'active',
        ]);

        LeaveRequest::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $person->id,
            'leave_type' => 'Vacation Leave',
            'date_from' => '2024-09-02',
            'date_to' => '2024-09-06',
            'reason' => 'Forced leave sample',
            'status' => 'approved',
        ]);
        LeaveRequest::create([
            'staff_user_id' => $staff->id,
            'personnel_id' => $person->id,
            'leave_type' => 'Mandatory/Forced Leave',
            'date_from' => '2024-09-09',
            'date_to' => '2024-09-09',
            'reason' => 'FL',
            'status' => 'approved',
        ]);

        $card->update([
            'adjustments' => [
                '2024-10' => ['vl_without_pay' => 2, 'sl_without_pay' => 0, 'particulars' => ''],
            ],
        ]);
        $card->refresh();

        $september = $this->month($service->months($person, $card, Carbon::parse('2024-10-31')), '2024-09');
        $october = $this->month($service->months($person, $card, Carbon::parse('2024-10-31')), '2024-10');

        $this->assertSame(6.0, $september['vl_with_pay']);
        $this->assertSame(0.0, $september['sl_with_pay']);
        $this->assertStringContainsString('VL', $september['particulars']);
        $this->assertStringContainsString('FL', $september['particulars']);
        $this->assertSame(round($september['vl_balance'] + 1.25, 3), $october['vl_balance']);
        $this->assertSame(2.0, $october['vl_without_pay']);

        $admin = AdminUser::create([
            'username' => 'leave-card-hr',
            'email' => 'leave-card-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leave-cards.show', $person))
            ->assertOk()
            ->assertSee('FEB 2024')
            ->assertSee('1.25');

        $teacher = Personnel::create([
            'name' => 'Felix Acob',
            'role' => 'TEACHER II',
            'employee_number' => '00-611',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leave-cards.index'))
            ->assertOk()
            ->assertSee('Christopher Beyer')
            ->assertDontSee('Felix Acob');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leave-cards.show', $teacher))
            ->assertNotFound();

        $through2025 = $service->yearlySummary($service->months($person, $card, Carbon::parse('2025-12-31')));
        $this->assertSame('FL 2024', $through2025[0]['remarks']);
        $this->assertSame(15.0, $through2025[1]['vl_earned']);
        $this->assertSame(15.0, $through2025[1]['sl_earned']);

        $path = app(\App\Services\LeaveCardWorkbook::class)->store($person, $card);
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $book->getSheetByName('General LC');
        $this->assertSame("EMPLOYEE'S LEAVE CARDS", $sheet->getCell('A1')->getValue());
        $this->assertSame('Absence Undertime W/ Pay', $sheet->getCell('D10')->getValue());
        $this->assertSame('FEB 2024', $sheet->getCell('A11')->getValue());
        $this->assertSame('RECORD OF LEAVE CREDITS', $book->getSheetByName('Abstract Summary (Yearly)')->getCell('A1')->getValue());
        @unlink($path);

        $this->assertFalse(app(LeaveBalanceService::class)->hasSufficientBalance($person, 'Vacation Leave', 500));
        $this->assertTrue(app(LeaveBalanceService::class)->hasSufficientBalance($person, 'Vacation Leave', 1));
    }

    /**
     * @param  list<array<string, mixed>>  $months
     * @return array<string, mixed>
     */
    private function month(array $months, string $key): array
    {
        foreach ($months as $month) {
            if ($month['key'] === $key) {
                return $month;
            }
        }

        $this->fail("Missing leave card month {$key}");
    }
}
