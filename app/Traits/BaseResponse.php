<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait BaseResponse
{

    /**
     * Building success response
     * @param $data
     * @param int $code
     * @return JsonResponse
     */
    public function successResponse(string $message = null, $data = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $message = $message ?? __('messages.success');
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
    /**
     * Building success response
     * @param $data
     * @param int $code
     * @return JsonResponse
     */

    public function errorResponse(string $message = null, $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $message = $message ?? __('messages.error_occurred');
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    public function exceptionResponse(Exception $e, string $defaultMessage = null, ?int $forceStatusCode = null): JsonResponse
    {
        if ($forceStatusCode !== null) {
            $statusCode = $forceStatusCode;
        } else {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : $e->getCode();

            if (!is_int($statusCode) || $statusCode < 100 || $statusCode >= 600) {
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
        }

        $message = config('app.debug') ? __($e->getMessage()) : ($defaultMessage ?? __('messages.error_occurred'));

        Log::error('Exception occurred', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }
}
