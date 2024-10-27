<?php

namespace App\Http\Controllers;

use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use Throwable;

abstract class Controller
{

    function throwableError(Throwable $e)  {
        logger($e->getMessage() , $e->getTrace());
        return ApiHelper::problemResponse('An error occurred while processing your request', ApiConstants::SERVER_ERR_CODE);
    }
}
