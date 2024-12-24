<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if (Auth::user()->type == 'admin') {
                    return redirect()->route('admin.home');
                } elseif (Auth::user()->type == 'manager') {
                    return redirect()->route('manager.home');
                } elseif (Auth::user()->type == 'staff') {
                    return redirect()->route('staff.home');
                } else {
                    return redirect()->route('user.home');
                }
            }
        }

        return $next($request);
    }
}