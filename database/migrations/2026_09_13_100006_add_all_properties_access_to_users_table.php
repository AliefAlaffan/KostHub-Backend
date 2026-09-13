<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag khusus staff: kalau true, staff ini otomatis punya akses ke
     * SEMUA property (termasuk yang dibuat setelahnya), tanpa perlu
     * di-assign satu-satu lewat tabel property_staff. Default false —
     * staff baru tetap ikut aturan lama (assign manual per property)
     * kecuali sengaja diaktifkan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_all_properties_access')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_all_properties_access');
        });
    }
};