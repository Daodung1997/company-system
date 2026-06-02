<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     *
     * @throws ForbiddenException
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser || ! $authenticatedUser->hasPermissionTo($permission)) {
            throw new ForbiddenException('auth.forbidden', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
