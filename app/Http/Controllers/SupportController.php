<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SupportController extends Controller
{
    public function index()
    {
        return view('support.index');
    }
}
