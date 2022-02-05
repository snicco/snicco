<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlMiddleware\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Snicco\Bridge\SignedUrlMiddleware\ValidateSignature;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\SignedUrl\Exception\InvalidSignature;
use Snicco\Component\SignedUrl\Exception\SignedUrlExpired;
use Snicco\Component\SignedUrl\Exception\SignedUrlUsageExceeded;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\UrlSigner;

final class ValidateSignatureTest extends MiddlewareTestCase
{

    private SignedUrlValidator $validator;
    private UrlSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new UrlSigner(
            $storage = new InMemoryStorage(),
            $hasher = new Sha256Hasher(Secret::generate())
        );
        $this->validator = new SignedUrlValidator(
            $storage,
            $hasher
        );
    }

    /**
     * @test
     */
    public function next_is_called_for_valid_signature(): void
    {
        $m = new ValidateSignature(
            $this->validator,
        );

        $link = $this->signer->sign('/foo', 10);

        $request = $this->frontendRequest($link->asString());

        $response = $this->runMiddleware($m, $request);

        $response->assertNextMiddlewareCalled();
        $response->psr()->assertOk();
    }

    /**
     * @test
     */
    public function next_is_not_called_for_invalid_signature(): void
    {
        $m = new ValidateSignature(
            $this->validator,
        );

        $link = $this->signer->sign('/foo', 10);

        $request = $this->frontendRequest(ltrim($link->asString(), '/') . '/bar/');

        $this->expectException(InvalidSignature::class);
        $this->runMiddleware($m, $request);
    }

    /**
     * @test
     */
    public function next_not_called_for_expired(): void
    {
        $m = new ValidateSignature(
            $this->validator,
        );

        $link = $this->signer->sign('/foo', 1);

        sleep(2);

        $request = $this->frontendRequest($link->asString());

        $this->expectException(SignedUrlExpired::class);
        $this->runMiddleware($m, $request);
    }

    /**
     * @test
     */
    public function next_not_called_for_used(): void
    {
        $m = new ValidateSignature(
            $this->validator,
        );

        $link = $this->signer->sign('/foo', 1, 2);

        $request = $this->frontendRequest($link->asString());

        $response = $this->runMiddleware($m, $request);
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertStatus(200);

        $response = $this->runMiddleware($m, $request);
        $response->assertNextMiddlewareCalled();
        $response->psr()->assertStatus(200);

        $this->expectException(SignedUrlUsageExceeded::class);
        $this->expectExceptionMessage('path [/foo]');
        $this->runMiddleware($m, $request);
    }

    /**
     * @test
     */
    public function additional_context_can_be_provided(): void
    {
        $m = new ValidateSignature(
            $this->validator,
            function (ServerRequestInterface $request) {
                return $request->getHeaderLine('User-Agent');
            }
        );

        $current_request = $this->frontendRequest()->withHeader('User-Agent', 'foo');

        $link = $this->signer->sign('/foo', 10, 2, $current_request->getHeaderLine('User-Agent'));

        $new_request = $this->frontendRequest($link->asString())->withHeader('User-Agent', 'bar');

        // User agent header matches
        $this->runMiddleware($m, $new_request->withHeader('User-Agent', 'foo'))->psr()->assertOk();

        $this->expectException(InvalidSignature::class);
        // User agent header did not match
        $this->runMiddleware($m, $new_request)->psr()->assertForbidden();
    }

}