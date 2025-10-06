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
            ['nama' => '1', 'kapasitas' => 2, 'status' => 'tersedia'],
            ['nama' => '2', 'kapasitas' => 4, 'status' => 'tersedia'],
            ['nama' => '3', 'kapasitas' => 2, 'status' => 'tersedia'],
            ['nama' => '4', 'kapasitas' => 6, 'status' => 'tersedia'],
            ['nama' => '5', 'kapasitas' => 4, 'status' => 'tersedia'],
        ]);
    }
}
