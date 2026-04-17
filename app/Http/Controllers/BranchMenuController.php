<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchMenuController extends Controller
{
    public $url = "menu-cabang";

    public function index()
    {
        return view('dashboard.branch-menu', ['title' => 'Ketersediaan Menu Cabang', 'url' => $this->url]);
    }
}
