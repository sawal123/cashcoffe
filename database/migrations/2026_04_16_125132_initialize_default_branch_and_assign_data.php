<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Cabang Default jika belum ada
        $mainBranch = DB::table('branches')->first();
        
        if (!$mainBranch) {
            $mainBranchId = DB::table('branches')->insertGetId([
                'nama_cabang' => 'Cabang Pusat',
                'alamat' => 'Alamat Pusat',
                'no_telp' => '-',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            $mainBranchId = $mainBranch->id;
        }

        // 2. Assign semua data lama ke Cabang Pusat (Data Healing)
        $tables = ['users', 'pesanans', 'ingredients', 'mejas', 'pengeluarans', 'riwayat_stocks'];
        
        foreach ($tables as $table) {
            DB::table($table)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Karena ini adalah data healing, rollback biasanya akan mengatur branch_id kembali ke null
        // Namun, karena secara arsitektural kita akan mewajibkan branch_id di kemudian hari,
        // ada baiknya kita membiarkan data ini tetap terhubung, atau kita bisa set null secara eksplisit.
        
        // Contoh if wanted:
        // $mainBranchId = DB::table('branches')->where('nama_cabang', 'Cabang Pusat')->value('id');
        // if ($mainBranchId) {
        //     $tables = ['users', 'pesanans', 'ingredients', 'mejas', 'pengeluarans', 'riwayat_stocks'];
        //     foreach ($tables as $table) {
        //         DB::table($table)->where('branch_id', $mainBranchId)->update(['branch_id' => null]);
        //     }
        //     DB::table('branches')->where('id', $mainBranchId)->delete();
        // }
    }
};
