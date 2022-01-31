<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\UrlSigner;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\Exception\InvalidSignature;
use Snicco\Component\SignedUrl\Exception\SignedUrlExpired;
use Snicco\Component\SignedUrl\Exception\SignedUrlUsageExceeded;

use function str_replace;

final class SignedUrlValidatorTest extends TestCase
{
    
    private UrlSigner       $url_signer;
    private InMemoryStorage $storage;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->storage = new InMemoryStorage();
        $this->hasher = new Sha256Hasher(Secret::generate());
        $this->url_signer = new UrlSigner($this->storage, $this->hasher);
    }
    
    /** @test */
    public function invalid_if_changed_path()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $validator->validate($signed_url->asString());
        
        $this->expectException(InvalidSignature::class);
        
        $string = str_replace('foo', 'bar', $signed_url->asString());
        
        $validator->validate($string);
    }
    
    /** @test */
    public function invalid_if_tampered_expiry()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $this->expectException(InvalidSignature::class);
        
        $string = preg_replace('/expires=\d+/', 'expires='.(time() + 100), $signed_url->asString());
        
        $validator->validate($string);
    }
    
    /** @test */
    public function invalid_if_tampered_signature()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $string = str_replace('signature=', 'signature=tampered', $signed_url->asString());
        try {
            $validator->validate($string);
            $this->fail("No exception thrown for tampered signature.");
        } catch (InvalidSignature $e) {
            $this->assertStringStartsWith('Invalid signature', $e->getMessage());
        }
    }
    
    /** @test */
    public function invalid_if_no_signature()
    {
        $e = time() + 10;
        $url = '/foo?expires='.$e;
        
        $this->expectException(InvalidSignature::class);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        $validator->validate($url);
    }
    
    /** @test */
    public function invalid_if_secret_is_changed()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        $validator = new SignedUrlValidator($this->storage, new Sha256Hasher(Secret::generate()));
        
        $this->expectException(InvalidSignature::class);
        $validator->validate($signed_url->asString());
    }
    
    /** @test */
    public function invalid_if_expired()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator(
            $this->storage,
            $this->hasher,
            $clock = new TestClock()
        );
        
        $validator->validate($signed_url->asString());
        
        $clock->travelIntoFuture(11);
        
        $this->expectException(SignedUrlExpired::class);
        
        $validator->validate($signed_url->asString());
    }
    
    /** @test */
    public function invalid_after_max_usage()
    {
        $signed_url = $this->url_signer->sign('/foo', 10, 2);
        $validator = new SignedUrlValidator(
            $this->storage,
            $this->hasher,
        );
        
        $validator->validate($signed_url->asString());
        $validator->validate($signed_url->asString());
        
        $this->expectException(SignedUrlUsageExceeded::class);
        
        $validator->validate($signed_url->asString());
    }
    
    /** @test */
    public function invalid_if_identifier_is_changed()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $this->expectException(InvalidSignature::class);
        
        $wrong_identifier = Base64UrlSafe::encode(random_bytes(16));
        
        $string = $signed_url->asString();
        preg_match_all('/signature=([^|]+)/', $string, $matches);
        $correct_identifier = $matches[1][0];
        
        $string = str_replace($correct_identifier, $wrong_identifier, $string);
        
        $validator->validate($string);
    }
    
    /**
     * @test
     */
    public function valid_if_created_with_path_and_validated_with_path()
    {
        $signed_url = $this->url_signer->sign('/foo', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $validator->validate($signed_url->asString());
        $this->assertTrue(true);
    }
    
    /** @test */
    public function valid_if_created_with_path_and_validated_with_full_url()
    {
        $signed_url = $this->url_signer->sign('/bar', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $validator->validate('https://foo.com'.$signed_url->asString());
        $this->assertTrue(true);
    }
    
    /** @test */
    public function valid_if_created_with_full_url_and_validated_with_full_url()
    {
        $signed_url = $this->url_signer->sign('https://foo.com/bar/baz/', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $validator->validate($signed_url->asString());
        $this->assertTrue(true);
    }
    
    /** @test */
    public function valid_if_created_with_full_url_and_validated_with_path()
    {
        $signed_url = $this->url_signer->sign('https://foo.com/bar/baz/', 10);
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        $without_host = str_replace('https://foo.com', '', $signed_url->asString());
        $validator->validate($without_host);
        $this->assertTrue(true);
    }
    
    /** @test */
    public function additional_request_data_can_be_added_for_validation()
    {
        $pre = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $_SERVER['HTTP_USER_AGENT'] = 'foobar';
        
        $signed_url = $this->url_signer->sign(
            'https://foo.com/bar/baz/',
            10,
            1,
            $_SERVER['HTTP_USER_AGENT']
        );
        
        $validator = new SignedUrlValidator($this->storage, $this->hasher);
        
        try {
            $validator->validate($signed_url->asString());
            $this->fail('Invalid signature passed validation.');
        } catch (InvalidSignature $e) {
            //
        }
        
        try {
            $validator->validate($signed_url->asString(), 'bogus');
            $this->fail('Invalid signature passed validation.');
        } catch (InvalidSignature $e) {
            //
        }
        
        $validator->validate($signed_url->asString(), $_SERVER['HTTP_USER_AGENT']);
        
        if ($pre === null) {
            unset($_SERVER['HTTP_USER_AGENT']);
        }
        else {
            $_SERVER['HTTP_USER_AGENT'] = $pre;
        }
        $this->assertTrue(true);
    }
    
}