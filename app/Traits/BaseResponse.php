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
    public function successResponse($message = 'Success', $data = null, $statusCode = 200, $request = null): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];

        // Add correlation ID if available
        if ($request && $request->header('X-Correlation-ID')) {
            $response['correlation_id'] = $request->header('X-Correlation-ID');
        } else {
            $response['correlation_id'] = uniqid('req_');
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array  $errors
     * @return JsonResponse
     */
    public function errorResponse($message = 'Something went wrong', $statusCode = 400, $errors = [], $request = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'data' => null
        ];

        // Add correlation ID if available
        if ($request && $request->header('X-Correlation-ID')) {
            $response['correlation_id'] = $request->header('X-Correlation-ID');
        } else {
            $response['correlation_id'] = uniqid('req_');
        }

        // Add errors if provided
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
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
