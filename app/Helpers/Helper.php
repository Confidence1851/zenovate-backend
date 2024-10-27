<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class Helper
{

    // function to convert time to 12 hour format
    public static function time24hrs($time)
    {
        $time = date("g:i A", strtotime($time));
        return $time;
    }

    // explode address into two parts and return with <br> and ,
    public static function explodeAddress($address)
    {
        $address = explode(',', $address);
        $address = implode(', <br>', $address);
        return $address;
    }

    // function that convert date from dd-mm-yyyy to Jan 01, 2019 format
    public static function dateFormat($date)
    {
        $date = date("M d, Y", strtotime($date));
        return $date;
    }

    // function that convert sentence to url format and make it all small letters
    public static function sentenceToUrl($sentence)
    {
        $sentence = strtolower($sentence);
        $sentence = str_replace(' ', '-', $sentence);
        return $sentence;
    }

    // reverse function of sentenceToUrl and make all first letter capital
    public static function urlToSentence($url)
    {
        $url = ucwords($url);
        $url = str_replace('-', ' ', $url);
        return $url;
    }

    // function to return first letter of string in capital
    public static function firstLetterCapital($string)
    {
        $string = ucfirst($string);
        return $string;
    }

    public static function encrypt($string)
    {
        return self::encrypt_decrypt("encrypt", $string);
    }

    public static function decrypt($string)
    {
        return self::encrypt_decrypt("decrypt", $string);
    }

    static function encrypt_decrypt($action, $string)
    {
        try {
            $output = false;

            $encrypt_method = "AES-256-CBC";
            $secret_key = 'Hg99JHShjdfhjhejkse@14447DP';
            $secret_iv = 'T0EHVn0dUIK888JSBGDD';

            // hash
            $key = hash('sha256', $secret_key);

            // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
            $iv = substr(hash('sha256', $secret_iv), 0, 16);

            if ($action == 'encrypt') {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                $output = base64_encode($output);
            } elseif ($action == 'decrypt') {
                $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }

            return $output;
        } catch (\Throwable $e) {
            return false;
        }
    }

    static function withDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir(trim($dir), 0777, true);
        }
    }


    /**Reads file from private storage */
    static function getFileFromPrivateStorage($fullpath, $disk = 'local')
    {
        if ((explode("/", $fullpath)[0] ?? "") === "app") {
            $fullpath = str_replace("app/", "", $fullpath);
        }
        if ($disk == 'public') {
            $disk = null;
        }
        $exists = Storage::disk($disk)->exists($fullpath);
        if ($exists) {
            $fileContents = Storage::disk($disk)->get($fullpath);
            $content = Storage::mimeType($fullpath);
            $response = Response::make($fileContents, 200);
            $response->header('Content-Type', $content);
            return $response;
        }
        return null;
    }

    static function deleteFileFromPrivateStorage($path, $disk = "local")
    {
        if ((explode("/", $path)[0] ?? "") === "app") {
            $path = str_replace("app/", "", $path);
        }

        $exists = Storage::disk($disk)->exists($path);
        if ($exists) {
            Storage::delete($path);
        }
        return $exists;
    }
    /**
    * @param $mode = ["encrypt" , "decrypt"]
    * @param $path =
    */
    static function readFileUrl($mode, $path)
    {
        if (strtolower($mode) == "encrypt") {
            $path = base64_encode($path);
            return route("web.read_file", $path);
        }
         return base64_decode($path);
    }
}
