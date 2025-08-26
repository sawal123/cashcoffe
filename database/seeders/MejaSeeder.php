<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MejaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mejas')->insert([
            ['nama' => 'Meja 1', 'kapasitas' => 2, 'status' => 'tersedia'],
            ['nama' => 'Meja 2', 'kapasitas' => 4, 'status' => 'digunakan'],
            ['nama' => 'Meja 3', 'kapasitas' => 2, 'status' => 'dipesan'],
            ['nama' => 'Meja 4', 'kapasitas' => 6, 'status' => 'tersedia'],
            ['nama' => 'Meja 5', 'kapasitas' => 4, 'status' => 'tersedia'],
        ]);
    }
}
