<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Holiday;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['username' => 'admin'],
            ['email' => 'admin@inhs.edu.ph', 'password' => 'Admin@123', 'role' => 'super_admin']
        );

        AdminUser::updateOrCreate(
            ['username' => 'hr'],
            ['email' => 'hr@inhs.edu.ph', 'password' => 'Hr@12345', 'role' => 'hr_officer']
        );

        AdminUser::updateOrCreate(
            ['username' => 'principal'],
            ['email' => 'principal@inhs.edu.ph', 'password' => 'Principal@123', 'role' => 'principal']
        );
    }
}
