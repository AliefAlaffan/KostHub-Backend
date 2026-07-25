<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->string('room_number', 20);
            $table->unsignedTinyInteger('floor')->default(1);
            $table->decimal('price', 12, 2);
            $table->decimal('size_m2', 6, 2)->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'inactive'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['property_id', 'floor', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
