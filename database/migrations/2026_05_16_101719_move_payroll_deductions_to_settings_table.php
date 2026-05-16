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
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('default_potongan_terlambat', 15, 2)->default(0);
            $table->decimal('default_potongan_alpha', 15, 2)->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'potongan_terlambat')) {
                $table->dropColumn('potongan_terlambat');
            }
            if (Schema::hasColumn('users', 'potongan_alpha')) {
                $table->dropColumn('potongan_alpha');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['default_potongan_terlambat', 'default_potongan_alpha']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('potongan_terlambat', 15, 2)->default(0);
            $table->decimal('potongan_alpha', 15, 2)->default(0);
        });
    }
};
