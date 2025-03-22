<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class PassportAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $client = Client::where('email', $request->email)->first();
    
        if ($client && Hash::check($request->password, $client->password)) {
            $token = $client->createToken('AppName')->accessToken;
            $userId = $client->id;
    
            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'userId' => $userId
            ], 200);
        }
    
        return response()->json([
            'message' => 'Invalid credentials.',
            'error' => 'Unauthenticated'
        ], 401);
    }
}
