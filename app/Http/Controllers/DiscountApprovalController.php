<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiscountApprovalController extends Controller
    public function index()
    {
        return view('dashboard.discount-approval', ['title' => 'Discount Approval Request']);
    }
