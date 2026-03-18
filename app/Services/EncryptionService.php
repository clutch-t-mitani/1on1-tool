<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * 感情ログの暗号化・復号を担う。
 * 暗号化キーは APP_KEY（AES-256-CBC）を使用する。
 */
final class EncryptionService
{
    public function encrypt(string $plainText): string
    {
        return Crypt::encryptString($plainText);
    }

    public function decrypt(string $cipherText): string
    {
        return Crypt::decryptString($cipherText);
    }
}
