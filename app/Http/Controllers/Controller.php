<?php

namespace App\Http\Controllers;

use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    function throwableError(Throwable $e)
    {
        logger($e->getMessage(), ["payload" => request()->all(), "trace" => $e->getTrace()]);
        return ApiHelper::problemResponse(
            'An error occurred while processing your request',
            ApiConstants::SERVER_ERR_CODE
        );
    }
}
