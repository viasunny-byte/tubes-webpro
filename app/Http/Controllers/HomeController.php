<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
PUBlic function index()
    {
        return view('index');
    }
}
