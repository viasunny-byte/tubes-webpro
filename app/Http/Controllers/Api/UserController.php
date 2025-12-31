<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get Authenticated User
     *
     * Mengambil data user yang sedang login.
     *
     * @group User
     *
     * @authenticated
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Aliya",
     *   "email": "user@gmail.com"
     * }
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
