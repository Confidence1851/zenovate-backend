<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GeneralException;
use App\Helpers\ApiConstants;
use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FormSessionResource;
use App\Models\FormSession;
use App\Services\General\WebsiteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DashboardController extends Controller
{
    function __construct(public WebsiteService $websiteService)
    {

    }
    function orders(Request $request)
    {
        try {
            $forms = FormSession::query()
                // ->where("user_id", $request->user()->id)
                ->latest()->paginate();
            $data = ApiHelper::collect_pagination($forms);
            $data["data"] = FormSessionResource::collection($data["data"]);
            return ApiHelper::validResponse(
                'Data retrieved successfully',
                $data
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



}
