<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale');

        $user = $request->user();
        if ($user !== null && Locales::isSupported($user->locale ?? null)) {
            $locale = $user->locale;
        }

        if ($request->session()->has('locale') && Locales::isSupported($request->session()->get('locale'))) {
            $locale = $request->session()->get('locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
