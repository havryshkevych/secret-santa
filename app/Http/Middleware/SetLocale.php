<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale');

        $routeLang = $request->route('lang');
        $sessionLocale = session('locale');

        if ($routeLang && in_array($routeLang, ['en', 'uk'])) {
            $locale = $routeLang;
            session(['locale' => $locale]);
        } elseif ($sessionLocale) {
            $locale = $sessionLocale;
        } elseif (auth()->check()) {
            $locale = auth()->user()->language ?? $locale;
        }

        if (! in_array($locale, ['uk', 'en'])) {
            $locale = 'uk';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
