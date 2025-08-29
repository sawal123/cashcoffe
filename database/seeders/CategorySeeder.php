<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'nama' => 'Coffee',
                'urutan' => 1,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
            [
                'nama' => 'Tea',
                'urutan' => 2,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
            [
                'nama' => 'Non-Coffee',
                'urutan' => 3,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
            [
                'nama' => 'Snacks',
                'urutan' => 4,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
            [
                'nama' => 'Dessert',
                'urutan' => 5,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
            [
                'nama' => 'Makanan Berat',
                'urutan' => 6,
                'is_active' => true,
                 'created_at' => now(),
    'updated_at' => now(),
            ],
        ]);
    }
}
