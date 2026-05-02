<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginLocalAppUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $user = User::firstOrCreate(
                ['email' => 'app@hooklab.local'],
                [
                    'name' => 'App',
                    'password' => Hash::make(env('LOCAL_APP_USER_PASSWORD', 'hooklab-local-dev')),
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user);
        }
        return $next($request);
    }
}
