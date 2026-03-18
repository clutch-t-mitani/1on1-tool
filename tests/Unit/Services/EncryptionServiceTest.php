<?php

namespace Tests\Unit\Services;

use App\Services\EncryptionService;
use Tests\TestCase;

final class EncryptionServiceTest extends TestCase
{
    private EncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EncryptionService();
    }

    public function test_暗号化した文字列を復号できる(): void
    {
        $plainText = 'テスト用の感情ログ';

        $cipherText = $this->service->encrypt($plainText);
        $decrypted  = $this->service->decrypt($cipherText);

        $this->assertSame($plainText, $decrypted);
    }

    public function test_暗号化後の文字列は平文と異なる(): void
    {
        $plainText  = 'テスト用の感情ログ';
        $cipherText = $this->service->encrypt($plainText);

        $this->assertNotSame($plainText, $cipherText);
    }

    public function test_同じ平文でも暗号化のたびに異なる文字列になる(): void
    {
        $plainText = 'テスト用の感情ログ';

        $cipher1 = $this->service->encrypt($plainText);
        $cipher2 = $this->service->encrypt($plainText);

        $this->assertNotSame($cipher1, $cipher2);
    }
}
