<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blueprint spec §7 PROPERTY MIGRATION melarang satu field generik
     * `bank_account` sebagai satu-satunya sumber info bank. Pisah jadi
     * bank_name, account_number, account_holder.
     *
     * `bank_account` SENGAJA tidak dihapus di migration ini (deprecated,
     * bukan destroyed) karena isinya adalah data production yang tidak bisa
     * di-parse otomatis secara aman (formatnya bebas teks). Drop kolom lama
     * dilakukan di migration terpisah setelah data direkonsiliasi manual
     * oleh admin dan kode (controller/frontend) sudah pindah ke field baru.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('bank_account');
            $table->string('account_number', 100)->nullable()->after('bank_name');
            $table->string('account_holder')->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_number', 'account_holder']);
        });
    }
};