<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->restrictOnDelete();
            $table->string('period', 7); // format YYYY-MM
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['contract_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
