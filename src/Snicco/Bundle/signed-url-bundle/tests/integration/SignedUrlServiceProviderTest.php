<?php

declare(strict_types=1);

namespace Tests\SignedUrlBundle\integration;

use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\UrlSigner;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\SignedUrlBundle\SignedUrlServiceProvider;
use Snicco\Bridge\SignedUrlMiddleware\CollectGarbage;
use Snicco\Bridge\SignedUrlMiddleware\ValidateSignature;

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