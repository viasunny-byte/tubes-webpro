<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ShopController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::where('status', 1)->latest()->paginate(12)
        );
    }

    public function show($slug)
    {
        return response()->json(
            Product::where('slug', $slug)->firstOrFail()
        );
    }
}