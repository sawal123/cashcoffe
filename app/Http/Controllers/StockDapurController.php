<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StockDapurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = "stock-dapur";
    public function index()
    {
        return view('dashboard.stock-dapur', ['title' => 'Stock Dapur', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view(
            'dashboard.stock.create-stock',
            [
                'stockId' => null,
                'title' => 'Stock / Create',
                'url' => $this->url,
                'submit' => 'simpan'
            ]
        );
    }
    public function add()
    {
        return view(
            'dashboard.stock.stock-add',
            [
                'stockId' => null,
                'title' => 'Stock / Create',
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
        return view('dashboard.stock.create-stock',
            [
                'stockId' => base64_decode($id),
                'title' => 'Stock / Edit',
                'url' => $this->url,
                'submit' => 'update'
            ]
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
