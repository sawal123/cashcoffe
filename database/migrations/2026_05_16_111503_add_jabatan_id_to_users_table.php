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
            $table->foreignId('jabatan_id')->nullable()->after('branch_id')->constrained('jabatans')->onDelete('set null');
            if (Schema::hasColumn('users', 'jabatan')) {
                $table->dropColumn('jabatan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('branch_id');
            $table->dropConstrainedForeignId('jabatan_id');
        });
    }
};
