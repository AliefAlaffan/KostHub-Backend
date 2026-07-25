<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('rent_amount', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['active', 'ending_soon', 'ended', 'renewed'])->default('active');
            $table->foreignId('renewed_from_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
