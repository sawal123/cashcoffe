<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = "branch";

    public function index()
    {
        return view('dashboard.branch', ['title' => 'Manajemen Cabang', 'url' => $this->url]);
    }
}
