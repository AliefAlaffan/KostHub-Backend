<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚠️ JANGAN DEPLOY MIGRATION INI SENDIRIAN.
     *
     * Spec §6 USERS MIGRATION mewajibkan role `tenant` diganti jadi
     * `customer` di final schema. Tapi per audit kode saat ini, minimal
     * 4 file di app/ (termasuk User::isTenant()) dan berbagai halaman
     * frontend masih literal-check role === 'tenant'. Migration ini HANYA
     * boleh dijalankan bersamaan dengan Phase 3 (rename semua referensi
     * 'tenant' role di backend + frontend), kalau tidak aplikasi langsung
     * rusak total (semua tenant existing dianggap role asing).
     *
     * Strategi enum 2 langkah dipakai supaya tidak ada window di mana
     * baris lama invalid terhadap enum baru:
     *   1) enum diperluas dulu (masih menerima 'tenant' & 'customer')
     *   2) data existing di-backfill
     *   3) enum dipersempit ke bentuk final
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','tenant','customer') NOT NULL");
        DB::statement("UPDATE users SET role = 'customer' WHERE role = 'tenant'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','customer') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','tenant','customer') NOT NULL");
        DB::statement("UPDATE users SET role = 'tenant' WHERE role = 'customer'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','tenant') NOT NULL");
    }
};