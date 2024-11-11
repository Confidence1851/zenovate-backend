<?php

namespace App\Services\General;


class UrlService
{

    public static function parse(string $url , $replace)
    {
        $url_data = parse_url($url);
        parse_str($url_data["query"] ?? null, $queries);
        foreach ($queries as $key => $value) {
            $queries[$key] = str_replace(array_keys($replace),array_values($replace), $value);
        }
        $url = url($url_data["path"]) . "?" . http_build_query($queries);
        return $url;
    }
}
