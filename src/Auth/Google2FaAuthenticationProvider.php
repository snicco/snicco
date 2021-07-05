<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use BaconQrCode\Renderer\Color\Rgb;
    use BaconQrCode\Renderer\Image\SvgImageBackEnd;
    use BaconQrCode\Renderer\ImageRenderer;
    use BaconQrCode\Renderer\RendererStyle\Fill;
    use BaconQrCode\Renderer\RendererStyle\RendererStyle;
    use BaconQrCode\Writer;
    use PragmaRX\Google2FA\Google2FA;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Support\WP;

    class Google2FaAuthenticationProvider implements TwoFactorAuthenticationProvider
    {

        use ResolveTwoFactorSecrets;
        use ResolvesUser;

        /**
         * The underlying library providing two factor authentication helper services.
         *
         * @var Google2FA
         */
        private $engine;

        private $encryptor;

        public function __construct(Google2FA $engine, EncryptorInterface $encryptor)
        {
            $this->engine = $engine;
            $this->encryptor = $encryptor;
        }

        public function generateSecretKey($length = 16, $prefix = '') : string
        {
            return $this->engine->generateSecretKey($length, $prefix);
        }

        public function qrCodeUrl(string $company_name, string $user_identifier, string $secret) :string
        {
            return $this->engine->getQRCodeUrl($company_name, $user_identifier, $secret);
        }

        public function verifyOneTimeCode(string $secret, string $code) :bool
        {
            return $this->engine->verifyKey($secret, $code);
        }

        public function renderQrCode() : string
        {

            $user = WP::currentUser();

            $url = $this->qrCodeUrl( WP::siteName() , $user->user_login, $this->twoFactorSecret($user->ID) );

            $svg = ( new Writer(
                new ImageRenderer(
                    new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                    new SvgImageBackEnd
                )
            ))->writeString($url);

            return trim(substr($svg, strpos($svg, "\n") + 1));


        }



    }