<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait BaseResponse
{
    /**
     * Success response.
     *
     * @param  string  $message
     * @param  mixed  $data
     * @param  int  $statusCode
     * @return JsonResponse
     */
    public function successResponse($message = 'Success', $data = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Error response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array  $errors
     * @return JsonResponse
     */
    public function errorResponse($message = 'Something went wrong', $statusCode = 400, $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' =>null
        ], $statusCode);
    }

    /**
     * Validation error response.
     *
     * @param  array  $errors
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    public function validationErrorResponse(array $errors, $message = 'Validation errors', $statusCode = 422): JsonResponse
    {
        return $this->errorResponse($message, $statusCode, $errors);
    }
}
