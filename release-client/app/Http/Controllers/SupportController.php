<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SupportController extends Controller
{
    public function index()
    {
        $superAdmins = User::where('role', 'super_admin')->get(['full_name', 'email']);
        return view('support.index', compact('superAdmins'));
    }
}
