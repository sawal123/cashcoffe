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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gaji_pokok')) {
                $table->decimal('gaji_pokok', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('users', 'tunjangan_harian')) {
                $table->decimal('tunjangan_harian', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('users', 'potongan_terlambat')) {
                $table->decimal('potongan_terlambat', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('users', 'potongan_alpha')) {
                $table->decimal('potongan_alpha', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('users', 'hak_cuti')) {
                $table->integer('hak_cuti')->default(12);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gaji_pokok',
                'tunjangan_harian',
                'potongan_terlambat',
                'potongan_alpha',
                'hak_cuti',
            ]);
        });
    }
};
