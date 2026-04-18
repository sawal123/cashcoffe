<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            // ID Kasir yang meminta (relasi ke tabel users)
            $table->foreignId('kasir_id')->constrained('users')->onDelete('cascade');

            // ID Diskon yang diminta (relasi ke tabel discounts)
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');

            // Status persetujuan: pending, approved, atau rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_approvals');
    }
};
