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
        $tables = [
            'users', 
            'mejas', 
            'ingredients', 
            'pesanans', 
            'pengeluarans', 
            'riwayat_stocks'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Add branch_id as nullable first to allow migrating existing databases.
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            });
        }

        // Add to_branch_id specifically for riwayat_stocks for transit feature
        Schema::table('riwayat_stocks', function (Blueprint $table) {
            $table->foreignId('to_branch_id')->nullable()->after('branch_id')->constrained('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users', 
            'mejas', 
            'ingredients', 
            'pesanans', 
            'pengeluarans', 
            'riwayat_stocks'
        ];

        Schema::table('riwayat_stocks', function (Blueprint $table) {
            $table->dropForeign(['to_branch_id']);
            $table->dropColumn('to_branch_id');
        });

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
