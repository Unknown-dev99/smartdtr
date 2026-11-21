<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminUser::create([
            'username' => 'admin',
            'email' => 'admin@inhs.edu.ph',
            'password' => 'Admin@123',
            'role' => 'super_admin',
        ]);
    }

    public function test_admin_can_log_in_with_username(): void
    {
        $this->post(route('login'), ['username' => 'admin', 'password' => 'Admin@123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');
    }

    public function test_admin_can_log_in_with_email(): void
    {
        $this->post(route('login'), ['username' => ' admin@inhs.edu.ph ', 'password' => 'Admin@123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->from(route('login'))
            ->post(route('login'), ['username' => 'admin@inhs.edu.ph', 'password' => 'wrong'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'Invalid credentials.']);

        $this->assertGuest('admin');
    }
}
