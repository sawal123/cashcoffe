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
        // 1. Tambah kolom satuan_id
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->foreignId('satuan_id')->nullable()->after('jumlah')->constrained('satuan_bahans')->onDelete('set null');
        });

        // 2. Data Healing (Migrasi data teks lama ke ID baru)
        try {
            // Pastikan satuan dasar ada
            $kg = \App\Models\SatuanBahan::firstOrCreate(['nama_satuan' => 'Kilogram']);
            $pcs = \App\Models\SatuanBahan::firstOrCreate(['nama_satuan' => 'Pcs']);

            $mappings = [
                'kg' => $kg->id, 
                'Kg' => $kg->id, 
                'Kilogram' => $kg->id, 
                'kilo' => $kg->id,
                'pcs' => $pcs->id, 
                'Pcs' => $pcs->id, 
                'PCS' => $pcs->id,
            ];

            foreach ($mappings as $text => $id) {
                \App\Models\Pengeluaran::where('satuan', $text)->update(['satuan_id' => $id]);
            }
        } catch (\Exception $e) {
            // Kita gunakan try-catch agar jika ada error di data healing, migrasi tabel tetap berhasil
            \Illuminate\Support\Facades\Log::error("Gagal melakukan data healing pada pengeluaran: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['satuan_id']);
            $table->dropColumn('satuan_id');
        });
    }
};
