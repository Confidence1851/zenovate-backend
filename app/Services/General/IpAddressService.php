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

    public static function getCountry()
    {
        $current = cache()->get("location_country");
        $location = DataConstants::CANADA;
        if (empty($current)) {
            $check = self::check(request()->ip());
            if ($check["status"] == "success") {
                $location = $check["countryCode"] == "US" ? DataConstants::USA : $location;
            }
            cache()->put("location_country", $location, now()->addMinutes(10));
        }else{
            $location = $current;
        }
        return $location;
    }
}
