<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['name' => 'New Year\'s Day', 'date' => '2026-01-01', 'type' => 'holiday'],
            ['name' => 'People Power Anniversary', 'date' => '2026-02-25', 'type' => 'holiday'],
            ['name' => 'Maundy Thursday', 'date' => '2026-04-02', 'type' => 'holiday'],
            ['name' => 'Good Friday', 'date' => '2026-04-03', 'type' => 'holiday'],
            ['name' => 'Labor Day', 'date' => '2026-05-01', 'type' => 'holiday'],
            ['name' => 'Independence Day', 'date' => '2026-06-12', 'type' => 'holiday'],
            ['name' => 'National Heroes Day', 'date' => '2026-08-31', 'type' => 'holiday'],
            ['name' => 'Bonifacio Day', 'date' => '2026-11-30', 'type' => 'holiday'],
            ['name' => 'Christmas Day', 'date' => '2026-12-25', 'type' => 'holiday'],
            ['name' => 'Rizal Day', 'date' => '2026-12-30', 'type' => 'holiday'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(['date' => $holiday['date']], $holiday);
        }
    }
}
