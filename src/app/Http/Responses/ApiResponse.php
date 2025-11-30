<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'code' => $code,
        ], $code);
    }

    public static function error($error = null, int $code = 400, $data = null)
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'error' => $error,
            'code' => $code,
        ], $code);
    }
}