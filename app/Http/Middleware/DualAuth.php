<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use Laravel\Sanctum\PersonalAccessToken;
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

        $plainPart = \Illuminate\Support\Str::after($token, '|');
        $passportToken = Token::where('token', hash('sha256', $plainPart))->first();

        if ($passportToken) {
            $client = ClientCredential::find($passportToken->user_id);
            if ($client) {
                Auth::setUser($client);
                return $next($request);
            }
        }

        $sanctumToken = PersonalAccessToken::where('token', hash('sha256', $token))->first();

        if ($sanctumToken) {
            $user = User::find($sanctumToken->tokenable_id);
            if ($user) {
                Auth::setUser($user);
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthenticated.',
            'error' => 'Unauthenticated',
        ], 401);
    }
}
