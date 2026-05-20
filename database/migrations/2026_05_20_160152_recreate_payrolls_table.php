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
        // Drop the old payrolls table if it exists
        Schema::dropIfExists('payrolls');

        // Recreate payrolls table with the new schema
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            
            // Pendapatan
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('insentif_double_shift', 15, 2)->default(0);
            
            // Potongan
            $table->decimal('potongan_alpha', 15, 2)->default(0);
            $table->decimal('potongan_telat', 15, 2)->default(0);
            $table->decimal('potongan_tidak_clock_out', 15, 2)->default(0);
            
            // Gaji Bersih
            $table->decimal('gaji_bersih', 15, 2)->default(0);
            
            $table->timestamps();

            // Composite unique index
            $table->unique(['user_id', 'periode_mulai', 'periode_selesai']);
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
