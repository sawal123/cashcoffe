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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('bulan'); // 1 - 12
            $table->integer('tahun'); // Misal: 2026
            
            // Rekap Kehadiran
            $table->integer('total_hadir')->default(0);
            $table->integer('total_terlambat')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_alpha')->default(0);
            
            // Rincian Komponen Gaji
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2)->default(0);
            
            $table->enum('status_pembayaran', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('tanggal_pembayaran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
