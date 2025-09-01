<?php

namespace Apiato\Core\Abstracts\Controllers;

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

        return new JsonResponse($data, $status);
    }

    public function responseWithCreatedTransform(mixed $data, string $transformerClass, array $meta = []): JsonResponse
    {
        return $this->responseWithTransform($data, $transformerClass, 201, $meta);
    }

    public function json($data, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function noContent($status = 204): JsonResponse
    {
        return new JsonResponse(null, $status);
    }
}
