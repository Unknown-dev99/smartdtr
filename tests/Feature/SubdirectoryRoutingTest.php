<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdirectoryRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_work_from_subdirectory_base_url(): void
    {
        $base = '/INHS SMART-DTR';

        $this->get($base.'/login', [
            'SCRIPT_NAME' => $base.'/index.php',
            'SCRIPT_FILENAME' => public_path('../index.php'),
        ])->assertOk();

        $this->get($base.'/', [
            'SCRIPT_NAME' => $base.'/index.php',
            'SCRIPT_FILENAME' => public_path('../index.php'),
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_subdirectory_root_redirects_to_dashboard(): void
    {
        $admin = AdminUser::create([
            'username' => 'subdiradmin',
            'password' => 'password',
        ]);

        $base = '/INHS SMART-DTR';

        $this->actingAs($admin, 'admin')
            ->get($base.'/', [
                'SCRIPT_NAME' => $base.'/index.php',
                'SCRIPT_FILENAME' => public_path('../index.php'),
            ])
            ->assertRedirect(route('admin.dashboard'));
    }
}
