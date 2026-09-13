<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keputusan produk (Langkah 3.4): billing_cycle 'yearly' dihapus.
     * Field ini gak pernah kepake (gak ada UI buat pilih yearly), semua
     * kontrak selalu monthly. Backfill dulu data lama (kalau ada baris
     * 'yearly' entah kenapa), baru persempit enum-nya.
     */
    public function up(): void
    {
        DB::statement("UPDATE contracts SET billing_cycle = 'monthly' WHERE billing_cycle = 'yearly'");
        DB::statement("ALTER TABLE contracts MODIFY billing_cycle ENUM('monthly') NOT NULL DEFAULT 'monthly'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly'");
    }
};