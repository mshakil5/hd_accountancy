<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use App\Models\User;
use App\Models\ClientCredential;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Passport::resolveUserUsing(function ($tokenId) {
            $token = Token::find($tokenId);
            if (!$token) return null;

            $user = User::find($token->user_id);
            if ($user) return $user;

            return ClientCredential::find($token->user_id);
        });
    }

    public function register()
    {
    }
}
