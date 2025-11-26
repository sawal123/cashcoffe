<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\Ingredients;
use Livewire\Attributes\On;
use App\Models\MenuIngredients as MenuIngredients;

class MenuIngredient extends Component
{
    public $menu_id;
    public $ingredient_id;
    public $qty;

    public function addIngredient()
    {
        $this->validate([
            'menu_id' => 'required',
            'ingredient_id' => 'required',
            'qty' => 'required|numeric|min:0.1',
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menu_id,
            'ingredient_id' => $this->ingredient_id,
            'qty' => $this->qty,
        ]);

        $this->reset(['ingredient_id', 'qty']);
    }
    
    public function removeIngredient($id)
    {
        MenuIngredients::where('id', $id)->delete();
    }

    public function getMenuIngredientsProperty()
    {
        if (!$this->menu_id) return [];
        // dd($this->menu_id);

        return MenuIngredients::where('menu_id', $this->menu_id)
            ->with('ingredient')
            ->get();
    }

    public function render()
    {
        return view('livewire.menu.menu-ingredient', [
            'menus' => Menu::all(),
            'ingredients' => Ingredients::all()
        ]);
    }
}
