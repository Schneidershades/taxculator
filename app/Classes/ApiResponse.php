<?php

namespace App\Classes;

use App\Traits\Api\ApiResponder;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    use ApiResponder;

    public function publicrespondError($message, $code): JsonResponse
    {
        return $this->respondError($message, $code);
    }
}
