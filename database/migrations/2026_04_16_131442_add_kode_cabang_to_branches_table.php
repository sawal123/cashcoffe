<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('kode_cabang')->nullable()->after('id');
        });

        // Set default kode_cabang for existing branch (Cabang Pusat)
        DB::table('branches')->where('nama_cabang', 'Cabang Pusat')->update([
            'kode_cabang' => 'PST01'
        ]);
        
        // Buat kode_cabang unique jika diperlukan, tapi kita set setelah inisialisasi agar aman
        Schema::table('branches', function (Blueprint $table) {
            $table->unique('kode_cabang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['kode_cabang']);
            $table->dropColumn('kode_cabang');
        });
    }
};
