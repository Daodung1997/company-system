<?php

namespace App\Exceptions;

use App\Supports\Facades\Response\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ValidationException $e, $request) {
            $errors = array_values($e->errors());

            return Response::failure([
                $errors[0][0],
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        });
    }

    public function render($request, Throwable $e)
    {

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            if ($e instanceof NotFoundHttpException || $e instanceof NotFoundResourceException) {
                return Response::notFound(! empty($e->getMessage())
                    ? $e->getMessage() : __('common.not_found'));
            }

            if ($e instanceof AuthenticationException) {
                if ($request->bearerToken()) {
                    try {
                        \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->checkOrFail();
                    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $expiredException) {
                        return Response::failure([
                            'token.expired',
                        ], ResponseAlias::HTTP_UNAUTHORIZED);
                    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $invalidException) {
                        return Response::failure([
                            'token.invalid',
                        ], ResponseAlias::HTTP_UNAUTHORIZED);
                    } catch (\Exception $ignore) {
                        // Ignore other JWT parsing errors and fallthrough to generic unauthorized
                    }
                }

                return Response::failure([
                    'auth.unauthorized',
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof ValidationException) {
                $errors = array_values($e->errors());

                return Response::failure([
                    $errors[0][0],
                ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($e instanceof InvalidAuthException) {
                if ($e->getMessage() == 'Token has expired') {
                    return Response::failure([
                        'token.expired',
                    ], ResponseAlias::HTTP_UNAUTHORIZED);
                }

                return Response::failure([
                    empty($e->getMessage()) ? 'auth.invalid' : $e->getMessage(),
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof UnauthorizedHttpException
                || $e instanceof TokenInvalidException
                || $e instanceof TokenExpiredException) {
                if ($e->getMessage() == 'Token has expired') {
                    return Response::failure([
                        'token.expired',
                    ], ResponseAlias::HTTP_UNAUTHORIZED);
                }

                if ($e->getMessage() == 'The token has been blacklisted') {
                    return Response::failure([
                        'auth.blacklist_token',
                    ], ResponseAlias::HTTP_UNAUTHORIZED);
                }

                if ($e->getMessage() == 'Token not provided'
                    || $e->getMessage() == 'Token has expired and can no longer be refreshed') {
                    return Response::failure([
                        'auth.unauthorized',
                    ], ResponseAlias::HTTP_UNAUTHORIZED);
                }

                return Response::failure([
                    empty($e->getMessage()) ? 'auth.unauthorized' : $e->getMessage(),
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof TokenNeedUpdatePasswordException) {
                return Response::failure([
                    empty($e->getMessage()) ? __('auth.need_update_password') : $e->getMessage(),
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            if ($e instanceof ForbiddenException) {
                return Response::failure([
                    empty($e->getMessage()) ? __('auth.forbidden') : $e->getMessage(),
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            // Not acceptable
            if ($e instanceof NotAcceptableHttpException) {
                return Response::failure([
                    $e->getMessage(),
                ], ResponseAlias::HTTP_NOT_ACCEPTABLE);
            }

            if ($e instanceof TokenBlacklistedException) {
                return Response::failure([
                    'auth.blacklist_token',
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof ThrottleRequestsException) {
                return Response::failure([
                    __('common.max_rate_limit'),
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof BusinessException) {
                return $e->render($request);
            }

            return Response::failure([
                empty($e->getMessage()) ? __('common.system_error') : $e->getMessage(),
            ]);
        }

        return parent::render($request, $e);
    }
}
