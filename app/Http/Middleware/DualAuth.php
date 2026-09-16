<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ClientCredential;
use App\Models\User;

class DualAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'Unauthenticated',
            ], 401);
        }

        $plainPart = Str::after($token, '|');
        $passportToken = \Laravel\Passport\Token::where('token', hash('sha256', $plainPart))->first();

        if ($passportToken) {
            $client = ClientCredential::find($passportToken->user_id);
            if ($client) {
                auth()->setUser($client);
                return $next($request);
            }
        }

        $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $token))->first();

        if ($sanctumToken) {
            $user = User::find($sanctumToken->tokenable_id);
            if ($user) {
                auth()->setUser($user);
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthenticated.',
            'error' => 'Unauthenticated',
        ], 401);
    }
}