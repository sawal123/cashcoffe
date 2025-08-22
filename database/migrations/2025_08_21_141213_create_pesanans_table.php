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
            $table->foreignId('mejas_id')->nullable()->constrained('mejas')->onDelete('set null');
            $table->foreignId('diskons_id')->nullable()->constrained('diskons')->onDelete('set null');
            $table->enum('status', ['pending', 'diproses', 'selesai', 'dibatalkan'])->default('pending');
            $table->enum('metode_pembayaran', ['tunai', 'qris', 'kartu'])->default('tunai');
            $table->decimal('total', 12, 2)->default(0);
            $table->text('catatan')->nullable();

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
