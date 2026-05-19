<?php

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        // Ensure ID 1, 2, 3
        Shift::updateOrCreate(['id' => 1], [
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '18:00:00',
            'denda_telat' => 20000,
            'maksimal_telat_menit' => 60,
        ]);

        Shift::updateOrCreate(['id' => 2], [
            'nama_shift' => 'Shift Siang',
            'jam_masuk' => '15:00:00',
            'jam_keluar' => '22:00:00',
            'denda_telat' => 20000,
            'maksimal_telat_menit' => 60,
        ]);

        Shift::updateOrCreate(['id' => 3], [
            'nama_shift' => 'Double Shift',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '22:00:00',
            'denda_telat' => 20000,
            'maksimal_telat_menit' => 60,
        ]);
    }
}
