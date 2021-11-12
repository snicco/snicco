<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Stubs;

use Snicco\Contracts\EncryptorInterface;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class TestTwoFactorProvider implements TwoFactorAuthenticationProvider
{
    
    private EncryptorInterface $encryptor;
    
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }
    
    public function qrCodeUrl(string $company_name, string $user_identifier, string $secret)
    {
    }
    
    public function verifyOneTimeCode(string $secret, string $code) :bool
    {
        return $code === '123456' && $secret === $this->generateSecretKey();
    }
    
    public function generateSecretKey($length = 16, $prefix = '') :string
    {
        return 'secret';
    }
    
    public function renderQrCode() :string
    {
        return 'code';
    }
    
}