<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image as Image;
use Throwable;

class ApiHelper
{
    public static function problemResponse(
        ?string $message,
        ?int $status_code,
        ?Request $request = null,
        ?Throwable $trace = null
    ) {
        $code = !empty($status_code) ? $status_code : ApiConstants::BAD_REQ_ERR_CODE;
        $traceMsg = empty($trace) ? null : $trace->getMessage();

        $body = [
            'message' => $message,
            'code' => $code,
            'success' => false,
            'error_debug' => $traceMsg,
        ];

        !empty($trace) ? logger($trace->getMessage(), $trace->getTrace()) : null;
        if (!empty($trace)) {
            // \Sentry\captureException($trace);
        }

        return response()->json($body)->setStatusCode($code);
    }

    /** Return error api response */
    public static function inputErrorResponse(?string $message = null, ?int $status_code = null, ?Request $request = null, ?ValidationException $trace = null)
    {
        $code = ($status_code != null) ? $status_code : '';

        $body = [
            'message' => $message,
            'code' => $code,
            'success' => false,
            'errors' => empty($trace) ? null : $trace->errors(),
        ];

        if (!empty($trace)) {
            // \Sentry\captureException($trace);
        }

        return response()->json($body)->setStatusCode($code);
    }

    /** Return valid api response */
    public static function validResponse(?string $message = null, $data = null, $request = null, $code = null)
    {
        if (is_null($data) || empty($data)) {
            $data = null;
        }
        $body = [
            'message' => $message,
            'data' => $data,
            'success' => true,
            'code' => $code ?? ApiConstants::GOOD_REQ_CODE,

        ];

        return response()->json($body)->setStatusCode($body['code']);
    }

    /**Returns formatted money value
     * @param float amount
     * @param int places
     * @param string symbol
     */

    /**Returns formatted date value
     * @param string date
     * @param string format
     */
    public static function format_date($date, $format = 'Y-m-d')
    {
        return date($format, strtotime($date));
    }

    /**Returns the available auth instance with user
     * @param bool $getUser
     */
    public static function auth($getUser = false)
    {
        return $getUser ? auth('api')->user() : auth('api');
    }

    public static function collect_pagination(LengthAwarePaginator $pagination, $appendQuery = true)
    {
        $request = request();
        unset($request['token']);
        if ($appendQuery) {
            $pagination->appends($request->query());
        }
        $all_pg_data = $pagination->toArray();
        unset($all_pg_data['links']); // remove links
        unset($all_pg_data['data']); // remove old data mapping

        $buildResponse['pagination_meta'] = $all_pg_data;
        $buildResponse['pagination_meta']['can_load_more'] = $all_pg_data['to'] < $all_pg_data['total'];
        $buildResponse['data'] = $pagination->getCollection();

        return $buildResponse;
    }
}
