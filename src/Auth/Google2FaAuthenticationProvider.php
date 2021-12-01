<?php

declare(strict_types=1);

namespace Snicco\Auth;

use Snicco\Support\WP;
use BaconQrCode\Writer;
use Snicco\Shared\Encryptor;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Color\Rgb;
use Snicco\Auth\Traits\ResolvesUser;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

class Google2FaAuthenticationProvider implements TwoFactorAuthenticationProvider
{
    
    use InteractsWithTwoFactorSecrets;
    use ResolvesUser;
    
    /**
     * The underlying library providing two-factor authentication helper services.
     */
    private Google2FA $engine;
    private Encryptor $encryptor;
    
    public function __construct(Google2FA $engine, Encryptor $encryptor)
    {
        $this->engine = $engine;
        $this->encryptor = $encryptor;
    }
    
    public function generateSecretKey($length = 16, $prefix = '') :string
    {
        return $this->engine->generateSecretKey($length, $prefix);
    }
    
    public function verifyOneTimeCode(string $secret, string $code) :bool
    {
        return $this->engine->verifyKey($secret, $code);
    }
    
    public function renderQrCode() :string
    {
        $user = WP::currentUser();
        
        $url = $this->qrCodeUrl(
            WP::siteName(),
            $user->user_login,
            $this->twoFactorSecret($user->ID)
        );
        
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(
                    192,
                    0,
                    null,
                    null,
                    Fill::uniformColor(
                        new Rgb(255, 255, 255),
                        new Rgb(45, 55, 72)
                    )
                ),
                new SvgImageBackEnd
            )
        ))->writeString($url);
        
        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
    
    public function qrCodeUrl(string $company_name, string $user_identifier, string $secret) :string
    {
        return $this->engine->getQRCodeUrl($company_name, $user_identifier, $secret);
    }
    
}