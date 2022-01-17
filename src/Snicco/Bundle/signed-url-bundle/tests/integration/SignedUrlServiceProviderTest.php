<?php

declare(strict_types=1);

namespace Tests\SignedUrlBundle\integration;

use Snicco\SignedUrl\Secret;
use Snicco\SignedUrl\UrlSigner;
use Snicco\SignedUrl\SignedUrlValidator;
use Snicco\SignedUrlMiddleware\CollectGarbage;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\SignedUrlMiddleware\ValidateSignature;
use Snicco\SignedUrlBundle\SignedUrlServiceProvider;

final class SignedUrlServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->withAddedConfig('signed_url.secret', Secret::generate()->asString());
        });
        parent::setUp();
    }
    
    /** @test */
    public function the_url_signer_is_bound()
    {
        $this->bootApp();
        $signer = $this->app[UrlSigner::class];
        $this->assertInstanceOf(UrlSigner::class, $signer);
    }
    
    /** @test */
    public function the_url_validator_is_bound()
    {
        $this->bootApp();
        $validator = $this->app[SignedUrlValidator::class];
        $this->assertInstanceOf(SignedUrlValidator::class, $validator);
    }
    
    /** @test */
    public function the_garbage_collection_middleware_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(CollectGarbage::class, $this->app[CollectGarbage::class]);
    }
    
    /** @test */
    public function the_validation_middleware_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(ValidateSignature::class, $this->app[ValidateSignature::class]);
    }
    
    /** @test */
    public function middleware_is_configured()
    {
        $this->bootApp();
        
        $global = $this->config->get('middleware.groups.global');
        $alias = $this->config->get('middleware.aliases');
        $this->assertContains(CollectGarbage::class, $global);
        $this->assertArrayHasKey('singed', $alias);
        $this->assertSame(ValidateSignature::class, $alias['singed']);
    }
    
    protected function packageProviders() :array
    {
        return [SignedUrlServiceProvider::class];
    }
    
}