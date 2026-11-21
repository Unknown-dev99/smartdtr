<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Personnel;
use App\Models\PersonnelDailyStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_analytics_print_separately_as_graph_or_table(): void
    {
        $teacher = Personnel::create([
            'name' => 'Teaching Staff',
            'role' => 'TCH III',
            'employee_number' => '00-501',
            'is_active' => true,
        ]);
        $staff = Personnel::create([
            'name' => 'Emelinda C Abu',
            'role' => 'ASSP II',
            'employee_number' => '00-502',
            'is_active' => true,
        ]);
        PersonnelDailyStatus::create([
            'personnel_id' => $teacher->id,
            'date' => '2026-11-02',
            'status' => 'Present',
        ]);
        PersonnelDailyStatus::create([
            'personnel_id' => $teacher->id,
            'date' => '2026-11-03',
            'status' => 'Late',
        ]);
        PersonnelDailyStatus::create([
            'personnel_id' => $staff->id,
            'date' => '2026-11-02',
            'status' => 'Absent',
        ]);
        PersonnelDailyStatus::create([
            'personnel_id' => $staff->id,
            'date' => '2026-11-03',
            'status' => 'On Leave',
        ]);

        $admin = AdminUser::create([
            'username' => 'analytics-hr',
            'email' => 'analytics-hr@example.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $query = ['from' => '2026-11-02', 'to' => '2026-11-03'];

        $this->actingAs($admin, 'admin')
            ->get(route('admin.reports', $query))
            ->assertOk()
            ->assertSee('Attendance summary')
            ->assertSee('Daily attendance trend')
            ->assertSee('Personnel attendance')
            ->assertSee('Teaching and non-teaching')
            ->assertSee('Print graph')
            ->assertSee('Print table');

        $table = $this->actingAs($admin, 'admin')
            ->get(route('admin.reports.analytics', $query + ['report' => 'summary', 'format' => 'table']));
        $table->assertOk()
            ->assertSee('Attendance summary')
            ->assertSee('Table')
            ->assertSee('Present')
            ->assertSee('Absent')
            ->assertDontSee('Personnel attendance');

        $graph = $this->actingAs($admin, 'admin')
            ->get(route('admin.reports.analytics', $query + ['report' => 'groups', 'format' => 'graph']));
        $graph->assertOk()
            ->assertSee('Teaching and non-teaching')
            ->assertSee('Graph')
            ->assertSee('printChart')
            ->assertDontSee('<table>');

        $people = $this->actingAs($admin, 'admin')
            ->get(route('admin.reports.analytics', $query + ['report' => 'personnel', 'format' => 'table']));
        $people->assertOk()
            ->assertSee('Emelinda C Abu')
            ->assertSee('Non-teaching')
            ->assertSee('Teaching');
    }
}
