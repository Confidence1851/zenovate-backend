<?php

namespace App\Helpers;

class EncryptionService
{
    private $secretKey;
    private $secretIv;
    private $cipher = 'aes-256-cbc';

    function __construct() {
    $this->secretKey = env("NEXT_PUBLIC_ENCRYPTION_KEY");
    $this->secretIv = env("NEXT_PUBLIC_ENCRYPTION_IV");

    }

    public function encrypt($data)
    {
        // Hash the secret key and IV
        $key = substr(hash('sha256', $this->secretKey), 0, 32);
        $iv = substr(hash('sha256', $this->secretIv), 0, 16);

        // Encrypt the data
        $encrypted = openssl_encrypt(json_encode($data), $this->cipher, $key, 0, $iv);

        // Return the encrypted data
        return $encrypted;
    }

    public function decrypt($encryptedData)
    {
        // Hash the secret key and IV
        $key = substr(hash('sha256', $this->secretKey), 0, 32);
        $iv = substr(hash('sha256', $this->secretIv), 0, 16);

        // Decrypt the data
        $decrypted = openssl_decrypt($encryptedData, $this->cipher, $key, 0, $iv);

        // Decode and return the decrypted data
        return json_decode($decrypted, true);
    }
}
