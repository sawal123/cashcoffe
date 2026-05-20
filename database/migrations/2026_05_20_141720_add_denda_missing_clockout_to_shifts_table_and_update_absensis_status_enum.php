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
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'denda_missing_clockout')) {
                $table->decimal('denda_missing_clockout', 10, 2)->default(0.00);
            }
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->enum('status', [
                'hadir',
                'terlambat',
                'alpha',
                'izin',
                'sakit',
                'cuti',
                'wfh',
                'dinas_luar',
                'complete',
                'tidak clock out'
            ])->default('hadir')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->enum('status', [
                'hadir',
                'terlambat',
                'alpha',
                'izin',
                'sakit',
                'cuti',
                'wfh',
                'dinas_luar',
                'complete'
            ])->default('hadir')->change();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('denda_missing_clockout');
        });
    }
};
