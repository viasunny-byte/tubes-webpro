<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login User
     *
     * Digunakan untuk autentikasi user dan menghasilkan token Sanctum.
     *
     * @group Authentication
     *
     * @bodyParam email string required Email user. Example: user@gmail.com
     * @bodyParam password string required Password user. Example: password123
     *
     * @response 200 {
     *   "token": "1|abcxyz",
     *   "user": {
     *     "id": 1,
     *     "name": "Aliya",
     *     "email": "user@gmail.com"
     *   }
     * }
     *
     * @response 401 {
     *   "message": "Invalid credentials"
     * }
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = $request->user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
}
