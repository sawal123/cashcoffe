<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public $url = "gudang";
    public function index()
    {
        return view('dashboard.gudang', ['title' => 'gudang', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
         return view(
            'dashboard.gudang.create',
            [
                'gudangId' => null,
                'title' => 'Gudang / Create',
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
        //
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
