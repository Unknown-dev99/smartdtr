<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUFFIXES = ['JR', 'JR.', 'SR', 'SR.', 'II', 'III', 'IV', 'V'];

    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('suffix', 20)->nullable()->after('middle_name');
        });

        foreach (DB::table('staff_users')->select('id', 'name')->get() as $row) {
            DB::table('staff_users')->where('id', $row->id)->update($this->splitName($row->name));
        }
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name', 'middle_name', 'suffix']);
        });
    }

    /**
     * @return array{last_name:string,first_name:?string,middle_name:?string,suffix:?string}
     */
    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $suffix = null;
        if (count($parts) > 2 && in_array(strtoupper(end($parts)), self::SUFFIXES, true)) {
            $suffix = array_pop($parts);
        }

        if ($parts === []) {
            return ['last_name' => '', 'first_name' => null, 'middle_name' => null, 'suffix' => $suffix];
        }

        $last = array_pop($parts);
        $first = array_shift($parts);

        return [
            'last_name' => $last,
            'first_name' => $first,
            'middle_name' => $parts === [] ? null : implode(' ', $parts),
            'suffix' => $suffix,
        ];
    }
};
