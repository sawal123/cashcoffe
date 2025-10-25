<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    public $url = 'pengeluaran';

    public function index()
    {
        return view('dashboard.pengeluaran', ['title' => 'Pengeluaran', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.pengeluaran.pengeluaran-create', [
            'pengeluaranId' => null,
            'title' => 'Pengeluaran / Create',
            'url' => $this->url,
            'submit' => 'simpan',
        ]);
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
