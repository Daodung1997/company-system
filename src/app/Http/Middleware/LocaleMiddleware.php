<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LocaleMiddleware extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        $locale = $user->locale ?? config('app.locale');

        if ($locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
