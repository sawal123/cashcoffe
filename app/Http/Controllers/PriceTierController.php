<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PriceTierController extends Controller
{
    public $url = "price-tier";

    public function index()
    {
        return view('dashboard.tier', ['title' => 'Manajemen Tier Harga', 'url' => $this->url]);
    }
}
