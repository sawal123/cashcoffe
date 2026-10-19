<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudangs', function (Blueprint $table) {
            // Legacy stock remains NULL. Only superadmin can access it until assigned.
            $table->foreignId('branch_id')->nullable()->after('id')
                ->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'nama_bahan']);
        });
    }

    public function down(): void
    {
        Schema::table('gudangs', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'nama_bahan']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
