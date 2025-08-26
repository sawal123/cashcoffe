<?php

namespace App\Http\Controllers;

use App\View\Components\Breadcrumb;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = "menu";
    public function index()
    {
        return view('dashboard.menu', ['title' => 'Menu', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view(
            'dashboard.menu.create',
            [
                'menuId' => null,
                'title' => 'Menu / Create',
                'url' => $this->url,
                'submit' => 'simpan'
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // dd($id);
        return view('dashboard.menu.create',
        ['menuId' => $id,
        'title' => 'Menu / Edit',
        'url' => $this->url
        ,'submit' => 'update']
    );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
