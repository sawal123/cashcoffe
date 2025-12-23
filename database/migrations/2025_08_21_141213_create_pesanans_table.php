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
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('mejas_id')->nullable()->constrained('mejas')->onDelete('set null');
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null');
            $table->enum('status', ['diproses', 'selesai', 'dibatalkan'])->default('diproses');
            $table->string('metode_pembayaran')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->decimal('uang_tunai', 12, 2)->nullable(); // uang yang dibayar kasir
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};
