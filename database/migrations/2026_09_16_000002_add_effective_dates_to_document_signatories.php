<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signatories', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->date('effective_from')->nullable()->after('title');
            $table->date('effective_until')->nullable()->after('effective_from');
            $table->index(['key', 'effective_from', 'effective_until'], 'document_signatories_effective_index');
        });
    }

    public function down(): void
    {
        Schema::table('document_signatories', function (Blueprint $table) {
            $table->dropIndex('document_signatories_effective_index');
            $table->dropColumn(['effective_from', 'effective_until']);
            $table->unique('key');
        });
    }
};
