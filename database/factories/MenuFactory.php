<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Menu::class;
    public function definition(): array
    {
        $categoryIds = Category::pluck('id')->all();

        return [
           'nama_menu' => $this->faker->unique()->words(2, true), 
            'categories_id' => $this->faker->randomElement($categoryIds),
            'harga' => $this->faker->numberBetween(10000, 100000),
            'is_active' => $this->faker->boolean(90),
            'deskripsi' => $this->faker->sentence(6),
            'gambar' => 'images/default_menu.jpg', // Ganti dengan path gambar default Anda
        ];
    }
}
