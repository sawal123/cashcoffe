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
        Schema::table('absensis', function (Blueprint $table) {
            if (!Schema::hasColumn('absensis', 'shift_id')) {
                $table->foreignId('shift_id')->after('tanggal')->nullable()->constrained('shifts')->nullOnDelete();
            }
            if (!Schema::hasColumn('absensis', 'is_double_shift')) {
                $table->boolean('is_double_shift')->default(false)->after('shift_id');
            }
            
            // Fix: MySQL needs an index for the foreign key. 
            // If we drop the unique one, we must have another one.
            $table->index('user_id'); 
            $table->dropUnique(['user_id', 'tanggal']);
            $table->unique(['user_id', 'tanggal', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tanggal', 'shift_id']);
            $table->unique(['user_id', 'tanggal']);
            $table->dropColumn(['shift_id', 'is_double_shift']);
        });
    }
};
