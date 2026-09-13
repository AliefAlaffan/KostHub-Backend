<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec §12 TENANT_DOCUMENTS MIGRATION: kolom `verified` (boolean) saja
     * tidak cukup untuk audit trail. Tambahkan verified_by + verified_at
     * agar tahu siapa dan kapan dokumen disetujui.
     */
    public function up(): void
    {
        Schema::table('tenant_documents', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('verified')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
    }
};