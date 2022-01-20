<?php

declare(strict_types=1);

namespace Snicco\SignedUrlBundle;

use Psr\Log\LoggerInterface;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\SignedUrlWP\Storage\WPDBStorage;
use Snicco\Component\SignedUrl\Hasher\Hasher;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Component\Core\Application\SharedKeys;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\Core\Contracts\ServiceProvider;
use Snicco\Bridge\SignedUrlMiddleware\CollectGarbage;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Bridge\SignedUrlMiddleware\ValidateSignature;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;

final class SignedUrlServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindHasher();
        $this->bindStorage();
        $this->bindUrlSigner();
        $this->bindValidator();
        $this->bindMiddleware();
        $this->bindMiddlewareConfig();
    }
    
    function bootstrap() :void
    {
        //
    }
    
    private function bindUrlSigner()
    {
        $this->container->singleton(UrlSigner::class, function () {
            return new UrlSigner(
                $this->container[SignedUrlStorage::class],
                $this->container[Hasher::class],
            );
        });
    }
    
    private function bindStorage()
    {
        $this->container->singleton(SignedUrlStorage::class, function () {
            return $this->app->isRunningUnitTest()
                ? new InMemoryStorage()
                : new WPDBStorage($this->config->get('signed_url.table_name', 'signed_urls'));
        });
    }
    
    private function bindHasher()
    {
        $this->container->singleton(Hasher::class, function () {
            return new Sha256Hasher(
                Secret::fromHexEncoded(
                    $this->config->get('signed_url.secret')
                )
            );
        });
    }
    
    private function bindValidator()
    {
        $this->container->singleton(SignedUrlValidator::class, function () {
            return new SignedUrlValidator(
                $this->container[SignedUrlStorage::class],
                $this->container[Hasher::class]
            );
        });
    }
    
    private function bindMiddleware()
    {
        $this->container->singleton(CollectGarbage::class, function () {
            return new CollectGarbage(
                $this->config->get('signed_url.gc_percentage', 2),
                $this->container[SignedUrlStorage::class],
                $this->container[LoggerInterface::class]
            );
        });
        
        $this->container->singleton(ValidateSignature::class, function () {
            $renderer = $this->container[SharedKeys::SIGNED_URL_RENDERER] ?? null;
            $context = $this->container[SharedKeys::SIGNED_URL_CONTEXT] ?? null;
            return new ValidateSignature(
                $this->container[SignedUrlValidator::class],
                $this->container[ResponseFactoryInterface::class],
                $this->container[LoggerInterface::class],
                $renderer,
                $this->config->get('signed.url.log_level', []),
                $context
            );
        });
    }
    
    private function bindMiddlewareConfig()
    {
        $this->config->extend('middleware.groups.global', [
            CollectGarbage::class,
        ]);
        
        $this->config->extend('middleware.aliases', [
            'singed' => ValidateSignature::class,
        ]);
    }
    
}