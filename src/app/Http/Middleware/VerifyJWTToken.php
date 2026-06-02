<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidAuthException;
use App\Exceptions\TokenNeedUpdatePasswordException;
use App\Supports\Facades\Response\Response;
use Carbon\Carbon;
use Closure;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws TokenNeedUpdatePasswordException
     */
    public function handle($request, Closure $next): mixed
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return Response::failure(['token.invalid'], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            $payload = JWTAuth::parseToken()->payload();

            $iat = Carbon::parse($payload->get('iat'))->timezone(config('app.timezone'));

            // $lastUpdatePasswordUser = Carbon::parse($user->change_password_at);
            // if (!empty($user->change_password_at) && $lastUpdatePasswordUser->gte($iat)) {
            //    throw new InvalidAuthException;
            // }

            if ($user->first_login) {
                throw new TokenNeedUpdatePasswordException;
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return Response::failure(['token.expired'], ResponseAlias::HTTP_UNAUTHORIZED);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return Response::failure(['token.invalid'], ResponseAlias::HTTP_UNAUTHORIZED);
        } catch (JWTException $e) {
            return Response::failure(['token.required'], ResponseAlias::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
