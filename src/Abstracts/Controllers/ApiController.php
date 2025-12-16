<?php

namespace Apiato\Core\Abstracts\Controllers;

use Apiato\Core\Foundation\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    public function responseWithTransform(
        mixed $data,
        string $transformerClass,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $data = fractal($data, new $transformerClass)->toArray();
        if (! empty($meta)) {
            $data['meta'] = $meta;
        }

        return ApiResponse::addMeta($meta)->create($data, $transformerClass)->json(null, $status);
    }

    public function responseWithCreatedTransform(mixed $data, string $transformerClass, array $meta = []): JsonResponse
    {
        return ApiResponse::addMeta($meta)->create($data, $transformerClass)->created();
    }

    public function json($data, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return ApiResponse::json($data, $status, $headers, $options);
    }

    public function noContent($status = 204): JsonResponse
    {
        return ApiResponse::noContent($status);
    }
}
