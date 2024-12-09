<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\General\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class WebsiteController extends Controller
{
    function __construct(public WebsiteService $websiteService) {

    }
    function contactUs(Request $request)
    {
        try {
            $this->websiteService->contactUs($request->all());
            return ApiHelper::validResponse(
                'Submitted successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }

    function newsletterSubscriber(Request $request)
    {
        try {
            $this->websiteService->newsletterSubscriber($request->all());
            return ApiHelper::validResponse(
                'Subscribed successfully',
            );
        } catch (ValidationException $e) {
            return ApiHelper::inputErrorResponse(
                $e->getMessage(),
                ApiConstants::VALIDATION_ERR_CODE,
                $request,
                $e
            );
        } catch (GeneralException $e) {
            return ApiHelper::problemResponse(
                $e->getMessage(),
                $e->getCode()
            );
        } catch (Throwable $e) {
            return $this->throwableError($e);
        }
    }


    function getFile($hash) {
        $decrypt = Helper::encrypt_decrypt("decrypt", $hash);
        return Helper::getFileFromPrivateStorage($decrypt);
    }

}
