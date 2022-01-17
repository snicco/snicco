<?php

declare(strict_types=1);

namespace Snicco\DefuseEncryption;

use Snicco\Component\Core\Shared\Encryptor;
use Snicco\Component\Core\Contracts\ServiceProvider;

final class DefuseEncryptionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->container->singleton(Encryptor::class, function () {
            return new DefuseEncryptor($this->config->get('app.encryption_key'));
        });
    }
    
    function bootstrap() :void
    {
        //
    }
    
}