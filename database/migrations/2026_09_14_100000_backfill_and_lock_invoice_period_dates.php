<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Invoice lama (sebelum rolling billing) formatnya cuma "YYYY-MM" di kolom
     * `period`, dan period_start/period_end masih kosong. Isi dulu berdasarkan
     * bulan-tahun itu (tanggal 1 sampai akhir bulan), baru kolomnya dikunci
     * NOT NULL supaya gak ada invoice baru yang lolos tanpa 2 kolom ini keisi.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE invoices
            SET
                period_start = STR_TO_DATE(CONCAT(period, '-01'), '%Y-%m-%d'),
                period_end = LAST_DAY(STR_TO_DATE(CONCAT(period, '-01'), '%Y-%m-%d'))
            WHERE period_start IS NULL
              AND period REGEXP '^[0-9]{4}-[0-9]{2}$'
        ");

        $remaining = DB::table('invoices')->whereNull('period_start')->count();

        if ($remaining > 0) {
            throw new \RuntimeException(
                "Masih ada {$remaining} invoice dengan period_start kosong yang formatnya gak dikenali. ".
                "Cek manual dulu isi kolom 'period' invoice-invoice itu sebelum lanjutkan migration ini."
            );
        }

        DB::statement('ALTER TABLE invoices MODIFY period_start DATE NOT NULL');
        DB::statement('ALTER TABLE invoices MODIFY period_end DATE NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices MODIFY period_start DATE NULL');
        DB::statement('ALTER TABLE invoices MODIFY period_end DATE NULL');
    }
};