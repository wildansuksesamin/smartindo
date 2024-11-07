<?php

namespace App\Encryptions;

class Cipher
{
    private $cipherKey;
    const AES_METHOD = 'AES-256-CBC';

    public function __construct()
    {
        $this->cipherKey = config('ciphers.cipher_key');
    }

    public function encrypt($text)
    {
        // Check versions with Heartbleed vulnerabilities
        if (OPENSSL_VERSION_NUMBER <= 268443727) {
            throw new \RuntimeException('OpenSSL Version too old');
        }

        $iv_size        = openssl_cipher_iv_length(Cipher::AES_METHOD);
        $iv             = openssl_random_pseudo_bytes($iv_size);
        $ciphertext     = openssl_encrypt($text, Cipher::AES_METHOD, $this->cipherKey, OPENSSL_RAW_DATA, $iv);
        $ciphertext_hex = base64_encode($ciphertext);
        $iv_hex         = base64_encode($iv);

        return "$iv_hex:$ciphertext_hex";
    }

    public function decrypt($text)
    {
        $parts = explode(':', $text);
        $iv = base64_decode($parts[0]);
        $ciphertext = base64_decode($parts[1]);
        return openssl_decrypt($ciphertext, Cipher::AES_METHOD, $this->cipherKey, OPENSSL_RAW_DATA, $iv);
    }
}