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
            if (!Schema::hasColumn('shifts', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('shifts', 'denda_telat')) {
                $table->decimal('denda_telat', 15, 2)->default(20000);
            }
            if (!Schema::hasColumn('shifts', 'maksimal_telat_menit')) {
                $table->integer('maksimal_telat_menit')->default(60);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jatah_cuti')) {
                $table->integer('jatah_cuti')->default(2);
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'radius_meter')) {
                $table->integer('radius_meter')->default(20);
            }
        });

        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('tanggal');
            $table->boolean('is_double_shift')->default(false);
            $table->timestamps();
        });
        
        // Update existing branches radius to 20 to ensure it matches the requested default if it already existed
        if (Schema::hasColumn('branches', 'radius')) {
             \Illuminate\Support\Facades\DB::table('branches')->update(['radius' => 20]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
        
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('radius_meter');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jatah_cuti');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'denda_telat', 'maksimal_telat_menit']);
        });
    }
};
