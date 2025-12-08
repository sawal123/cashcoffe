<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AbsenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = "absense";
    public function index()
    {
        return view('dashboard.absense', ['title' => 'Absense', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
        return view('dashboard.absense.show-absense', ['userId' => $id,'title' => 'Absense', 'url' => $this->url]);
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
