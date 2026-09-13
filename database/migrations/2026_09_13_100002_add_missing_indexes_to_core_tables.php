<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index yang direkomendasikan spec §10, §13, §6 tapi belum ada
     * di migration awal. Query occupancy/report/scheduler yang filter
     * berdasarkan kombinasi ini sering jalan tanpa index sebelumnya.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('status');
            $table->index(['property_id', 'status']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('start_date');
            $table->index(['tenant_id', 'status']);
            $table->index(['room_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['property_id', 'status']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['room_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};