<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
PUBlic function index()
    {
        return view('index');
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $result = Product::where('name','LIKE',"%{$query}%")->get()->take(8);
        return response()->json($result);
    }
}
