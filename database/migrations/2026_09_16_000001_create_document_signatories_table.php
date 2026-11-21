<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('document_signatories')->insert([
            ['key' => 'dtr_verifier', 'name' => 'MARIE ROSE P. RAMOS', 'title' => 'Authorized Officer', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'school_head', 'name' => 'PRISCA ROMELA C. BUENAVENTURA', 'title' => 'School Head', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'assistant_principal', 'name' => 'PRISCA ROMELA C. BUENAVENTURA', 'title' => 'Assistant Principal II', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'assistant_sds', 'name' => 'CHERYL R. RAMIRO', 'title' => 'Assistant Schools Division Superintendent', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'schools_division_superintendent', 'name' => 'EDUARDO C. ESCORPISO, JR.', 'title' => 'Schools Division Superintendent', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ris_issuer', 'name' => 'JONALYN B. TUDDAO', 'title' => 'AO II', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'supply_custodian', 'name' => null, 'title' => 'Supply / Property Custodian', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'accounting_staff', 'name' => null, 'title' => 'Designated Accounting Staff', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatories');
    }
};
