<?php

namespace App\Supports\Facades\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Facade;

/**
 * Class Response
 *
 * @method static JsonResponse success(array $data = [])
 * @method static JsonResponse created(array $data = [])
 * @method static JsonResponse failure(array $data, int $status = 500)
 * @method static JsonResponse pagination(AnonymousResourceCollection $data, int $total, int $current, int $limit, array $metadata = [])
 * @method static JsonResponse notFound(string $message = null)
 * @method static JsonResponse resourceConflict(array $data, array $actions, int $status = 409)
 */
class Response extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'response';
    }
}
