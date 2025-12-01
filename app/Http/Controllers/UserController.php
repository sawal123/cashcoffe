<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = 'user';

    public function index()
    {
        return view('dashboard.user', ['title' => 'User', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
     public function create()
    {
        return view('dashboard.user.create-user',
            [
                'userId' => null,
                'title' => 'User / Create',
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
         return view('dashboard.user.create-user',
            [
                'userId' => $id,
                'title' => 'User / Edit',
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
