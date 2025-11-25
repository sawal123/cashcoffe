<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $url = "member";
    public function index()
    {
        return view('dashboard.member', ['title' => 'Member', 'url' => $this->url]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.member.create-member',
            [
                'memberId' => null,
                'title' => 'Member / Create',
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
        return view('dashboard.member.create-member',
            [
                'memberId' => $id,
                'title' => 'Member / Edit',
                'url' => $this->url,
                'submit' => 'simpan'
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
