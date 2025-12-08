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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_masuk');
            $table->time('jam_keluar')->nullable();
            $table->string('lokasi')->nullable(); // bisa koordinat atau alamat
            $table->string('foto')->nullable();   // path foto absensi
            $table->string('foto_keluar')->nullable();
            $table->string('lokasi_keluar')->nullable();
            // $table->string('status', 20);
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
            ])->default('hadir');
            $table->text('keterangan')->nullable();
            $table->unique(['user_id', 'tanggal']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
