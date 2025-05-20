<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientCredential;
use Illuminate\Support\Facades\Hash;

class PassportAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $client = ClientCredential::where('email', $request->email)->first();
    
        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error' => 'Unauthenticated'
            ], 401);
        }

        if (!$client->status) {
            return response()->json([
                'message' => 'Account is inactive.',
                'error' => 'Unauthorized'
            ], 403);
        }

        $token = $client->createToken('ClientApp')->accessToken;
        $userId = $client->id;
    
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'userId' => $userId,
            'user' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone
            ]
        ], 200);
    }
}