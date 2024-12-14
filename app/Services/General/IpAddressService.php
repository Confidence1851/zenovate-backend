<?php

namespace App\Services\General;

use App\Models\User;
use App\Services\Form\DataConstants;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class IpAddressService
{


    public static function check($ip_address)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://ip-api.com/php/$ip_address",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $process = curl_exec($curl);
        curl_close($curl);
        $data = unserialize($process);
        return $data ?? [];
    }

    public static function info($ip_address = null , $force = false)
    {
        $ip_address = $ip_address ?? request()->ip();
        $key = "location_country_{$ip_address}";

        if($force){
            cache()->forget($key);
        }

        $info = cache()->get($key , []);
        if (empty($info)) {
            $check = self::check($ip_address);
            if ($check["status"] == "success") {
                cache()->put($key, $check, now()->addMinutes(10));
                $currencies = [
                    "CA" => "CAD",
                    "US" => "USD",
                ];
                $check["currency"] = $currencies[$check["countryCode"]] ?? "USD";
                $info = $check;
            }
        }

        return $info;
    }
}
