<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices MODIFY period VARCHAR(60) NOT NULL');

        Schema::table('invoices', function ($table) {
            $table->unique(['contract_id', 'period_start', 'period_end'], 'invoices_contract_period_dates_unique');
            $table->dropUnique(['contract_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function ($table) {
            $table->unique(['contract_id', 'period']);
            $table->dropUnique('invoices_contract_period_dates_unique');
        });

        DB::statement('ALTER TABLE invoices MODIFY period VARCHAR(7) NOT NULL');
    }
};