<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $username = strtolower($user->telegram_username ?? '');
        $isAdminConfig = $username && in_array($username, config('services.telegram.admin_usernames', []));

        if (! $user->is_admin && ! $isAdminConfig) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
