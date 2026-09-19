<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Property belum punya kolom foto sama sekali - dibutuhkan buat halaman
     * Detail Properti & card di halaman daftar (desain Stitch nampilin foto gedung).
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};