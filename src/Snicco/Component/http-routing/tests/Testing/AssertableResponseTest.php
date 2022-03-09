<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use Closure;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Cookies;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Testing\AssertableCookie;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use function json_encode;

final class AssertableResponseTest extends TestCase
{
    use CreateTestPsr17Factories;

    private ResponseFactory $response_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response_factory = $this->createResponseFactory();
    }

    /**
     * @test
     */
    public function test_construct_for_response(): void
    {
        $response = $this->response_factory->html('foo');

        $test_response = new AssertableResponse($response);
        $this->assertInstanceOf(AssertableResponse::class, $test_response);
        $this->assertNotInstanceOf(ResponseInterface::class, $test_response);
    }

    /**
     * @test
     */
    public function test_body(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $this->assertSame('foo', $response->body());
    }

    /**
     * @test
     */
    public function test_body_with_empty_body(): void
    {
        $response = new AssertableResponse($this->response_factory->createResponse());

        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function test_assert_delegated_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate());

        $response->assertDelegated();
    }

    /**
     * @test
     */
    public function test_assert_delegated_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $this->expectFailureWithMessageContaining(
            'Expected response to be instance of',
            fn () => $response->assertDelegated()
        );
    }

    /**
     * @test
     */
    public function test_assert_not_delegated_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $response->assertNotDelegated();
    }

    /**
     * @test
     */
    public function test_assert_not_delegated_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate());

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated',
            fn () => $response->assertNotDelegated()
        );
    }

    /**
     * @test
     */
    public function test_assertSuccessful_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 204));

        $response->assertSuccessful();
    }

    /**
     * @test
     */
    public function test_assertSuccessful_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 400));

        $this->expectFailureWithMessageContaining(
            'Response status code [400] is not a success status code.',
            fn () => $response->assertSuccessful()
        );
    }

    /**
     * @test
     */
    public function test_assertSuccessful_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(204));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertSuccessful()
        );
    }

    /**
     * @test
     */
    public function test_assertOk_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 200));

        $response->assertOk();
    }

    /**
     * @test
     */
    public function test_assertOk_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 201));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [200].\nGot [201].",
            fn () => $response->assertOk()
        );
    }

    /**
     * @test
     */
    public function test_assertOK_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(200));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertOk()
        );
    }

    /**
     * @test
     */
    public function test_assertCreated_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 201));

        $response->assertCreated();
    }

    /**
     * @test
     */
    public function test_assertCreated_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 204));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [201].\nGot [204].",
            fn () => $response->assertCreated()
        );
    }

    /**
     * @test
     */
    public function test_assertCreated_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(201));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertCreated()
        );
    }

    /**
     * @test
     */
    public function test_assertNotContent_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->noContent());

        $response->assertNoContent();
    }

    /**
     * @test
     */
    public function test_assertNoContent_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->noContent()->withStatus(200));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [204].\nGot [200].",
            fn () => $response->assertNoContent()
        );
    }

    /**
     * @test
     */
    public function test_assertNoContent_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(204));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertNoContent()
        );
    }

    /**
     * @test
     */
    public function test_assertNoContent_fails_204_status_but_non_empty_body(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo')->withStatus(204));

        $this->expectFailureWithMessageContaining(
            'Response code matches expected [204] but the response body is not empty.',
            fn () => $response->assertNoContent()
        );
    }

    /**
     * @test
     */
    public function test_assertStatus_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->createResponse(201));

        $response->assertStatus(201);
    }

    /**
     * @test
     */
    public function test_assertStatus_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->createResponse(201));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [301].\nGot [201].",
            fn () => $response->assertStatus(301)
        );
    }

    /**
     * @test
     */
    public function test_assertStatus_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(301));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertStatus(301)
        );
    }

    /**
     * @test
     */
    public function test_assertNotFound_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 404));

        $response->assertNotFound();
    }

    /**
     * @test
     */
    public function test_assertNotFound_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 401));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [404].\nGot [401].",
            fn () => $response->assertNotFound()
        );
    }

    /**
     * @test
     */
    public function test_assertNotFound_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(404));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertNotFound()
        );
    }

    /**
     * @test
     */
    public function test_assertForbidden_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 403));

        $response->assertForbidden();
    }

    /**
     * @test
     */
    public function test_assertForbidden_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 401));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [403].\nGot [401].",
            fn () => $response->assertForbidden()
        );
    }

    /**
     * @test
     */
    public function test_assertForbidden_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(403));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertForbidden()
        );
    }

    /**
     * @test
     */
    public function test_assertUnauthorized_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 401));

        $response->assertUnauthorized();
    }

    /**
     * @test
     */
    public function test_assertUnauthorized_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo', 403));

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [401].\nGot [403].",
            fn () => $response->assertUnauthorized()
        );
    }

    /**
     * @test
     */
    public function test_assertUnauthorized_fails_for_delegated_responses(): void
    {
        $response = new AssertableResponse($this->response_factory->delegate()->withStatus(401));

        $this->expectFailureWithMessageContaining(
            'Expected response to not be delegated.',
            fn () => $response->assertUnauthorized()
        );
    }

    /**
     * @test
     */
    public function test_assertHeader_can_pass(): void
    {
        $response =
            new AssertableResponse($this->response_factory->createResponse()->withHeader('X-FOO', 'BAR'));

        $response->assertHeader('X-FOO');
        $response->assertHeader('X-FOO', 'BAR');
        $response->assertHeader('x-foo', 'BAR');

        $response =
            new AssertableResponse($this->response_factory->createResponse()->withHeader('x-foo', 'BAR'));

        $response->assertHeader('X-FOO');
        $response->assertHeader('X-FOO', 'BAR');
        $response->assertHeader('x-foo', 'BAR');
    }

    /**
     * @test
     */
    public function test_assert_header_can_fail(): void
    {
        $response =
            new AssertableResponse($this->response_factory->createResponse()->withHeader('X-FOO', 'BAR'));

        $this->expectFailureWithMessageContaining(
            'Response does not have header [X-BAR].',
            fn () => $response->assertHeader('X-BAR')
        );

        $this->expectFailureWithMessageContaining(
            'Value [BAR] for header [X-FOO] does not match [BAZ].',
            fn () => $response->assertHeader('X-FOO', 'BAZ')
        );
    }

    /**
     * @test
     */
    public function test_assertHeaderMissing_can_pass(): void
    {
        $response =
            new AssertableResponse($this->response_factory->createResponse()->withHeader('X-FOO', 'BAR'));

        $response->assertHeaderMissing('X-BAR');
    }

    /**
     * @test
     */
    public function test_assertHeaderMissing_can_fail(): void
    {
        $response =
            new AssertableResponse($this->response_factory->createResponse()->withHeader('X-FOO', 'BAR'));

        $this->expectFailureWithMessageContaining(
            'Header [X-FOO] was not expected to be in the response.',
            fn () => $response->assertHeaderMissing('X-FOO')
        );

        $this->expectFailureWithMessageContaining(
            'Header [x-foo] was not expected to be in the response.',
            fn () => $response->assertHeaderMissing('x-foo')
        );
    }

    /**
     * @test
     */
    public function test_assertLocation_can_pass(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->createResponse()->withHeader('location', '/foo/bar?baz=biz')
        );

        $response->assertLocation('/foo/bar?baz=biz');
    }

    /**
     * @test
     */
    public function test_assertLocation_can_fail(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->createResponse()->withHeader('location', '/foo/bar?baz=biz')
        );

        $this->expectFailureWithMessageContaining(
            "Expected location header to be [/foo/bar].\nGot [/foo/bar?baz=biz].",
            fn () => $response->assertLocation('/foo/bar')
        );
    }

    /**
     * @test
     */
    public function test_getAssertableCookie_fails_if_set_cookie_header_is_not_present(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->createResponse()
        );

        $this->expectFailureWithMessageContaining(
            'Response does not have header [set-cookie]',
            fn () => $response->getAssertableCookie('foo')
        );
    }

    /**
     * @test
     */
    public function test_getAssertableCookie_fails_if_the_cookie_is_not_present(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->createResponse()->withAddedHeader('set-cookie', 'foo=bar')
        );

        $this->expectFailureWithMessageContaining(
            'Response does not have cookie matching name [bar].',
            fn () => $response->getAssertableCookie('bar')
        );
    }

    /**
     * @test
     */
    public function test_getAssertableCookie_fails_if_the_cookie_present_multiple_times(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->createResponse()
                ->withAddedHeader('set-cookie', 'foo=bar')
                ->withAddedHeader('set-cookie', 'foo=baz')
        );

        $this->expectFailureWithMessageContaining(
            'The cookie [foo] was sent [2] times.',
            fn () => $response->getAssertableCookie('foo')
        );
    }

    /**
     * @test
     */
    public function test_getAssertableCookie_can_pass_and_returns_assertable_cookie(): void
    {
        $cookie = (new Cookie('foo', 'bar'));
        $cookies = new Cookies();
        $cookies = $cookies->withCookie($cookie);

        /** @var array{0:string} $headers */
        $headers = $cookies->toHeaders();

        $response = new AssertableResponse(
            $this->response_factory->createResponse()
                ->withAddedHeader('set-cookie', $headers[0])
        );

        $cookie = $response->getAssertableCookie('foo');
        $this->assertInstanceOf(AssertableCookie::class, $cookie);
        $this->assertSame('bar', $cookie->value);
        $this->assertSame('foo', $cookie->name);
        $this->assertTrue($cookie->http_only);
        $this->assertSame('Lax', $cookie->same_site);
        $this->assertSame('/', $cookie->path);
        $this->assertTrue($cookie->secure);
    }

    /**
     * @test
     */
    public function test_assertRedirect_can_pass(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->redirect('/foo/bar', 301)
        );

        $response->assertRedirect();
        $response->assertRedirect('/foo/bar');
        $response->assertRedirect('/foo/bar', 301);
    }

    /**
     * @test
     */
    public function test_assertRedirect_can_fail(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->html('foo')
        );

        $this->expectFailureWithMessageContaining(
            'Status code [200] is not a redirection status code.',
            fn () => $response->assertRedirect()
        );

        $response = new AssertableResponse(
            $this->response_factory->redirect('/foo/bar', 301)
        );

        $this->expectFailureWithMessageContaining(
            "Expected location header to be [/foo/baz].\nGot [/foo/bar]",
            fn () => $response->assertRedirect('/foo/baz')
        );

        $this->expectFailureWithMessageContaining(
            "Expected response status code to be [302].\nGot [301].",
            fn () => $response->assertRedirect('/foo/bar', 302)
        );
    }

    /**
     * @test
     */
    public function test_assertRedirectPath_can_pass(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->redirect('/foo/bar?baz=biz', 301)
        );

        $response->assertRedirectPath('/foo/bar');
        $response->assertRedirectPath('/foo/bar', 301);
    }

    /**
     * @test
     */
    public function test_assertRedirectPath_can_fail(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->redirect('/foo/bar?baz=biz', 301)
        );

        $this->expectFailureWithMessageContaining(
            'Redirect path [/foo/baz] does not match location header [/foo/bar?baz=biz].',
            fn () => $response->assertRedirectPath('/foo/baz')
        );

        $this->expectFailureWithMessageContaining(
            'Expected response status code to be [302]',
            fn () => $response->assertRedirectPath('/foo/bar', 302)
        );
    }

    /**
     * @test
     */
    public function test_assertContentType_can_pass(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->html('foo')
        );

        $response->assertContentType('text/html');
        $response->assertContentType('text/html', 'UTF-8');
    }

    /**
     * @test
     */
    public function test_assertContentType_can_fail(): void
    {
        $response = new AssertableResponse(
            $this->response_factory->json([
                'foo' => 'bar',
            ])
        );

        $this->expectFailureWithMessageContaining(
            'Expected content-type [text/html; charset=UTF-8] but received [application/json]',
            fn () => $response->assertContentType('text/html')
        );

        $response = new AssertableResponse(
            $this->response_factory->html('foo')
        );

        $this->expectFailureWithMessageContaining(
            'Expected content-type [text/html; charset=bogus] but received [text/html; charset=UTF-8]',
            fn () => $response->assertContentType('text/html', 'bogus')
        );
    }

    /**
     * @test
     */
    public function test_assertSeeHtml_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo</h1>'));

        $response->assertSeeHtml('foo');
        $response->assertSeeHtml('<h1>foo</h1>');
    }

    /**
     * @test
     */
    public function test_assertSeeHtml_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo</h1>'));

        $this->expectFailureWithMessageContaining(
            'Response body does not contain [bar]',
            fn () => $response->assertSeeHtml('bar')
        );

        $this->expectFailureWithMessageContaining(
            'Response body does not contain [<h1>bar</h1>]',
            fn () => $response->assertSeeHtml('<h1>bar</h1>')
        );
    }

    /**
     * @test
     */
    public function test_assertDontSeeHtml_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo</h1>'));

        $response->assertDontSeeHtml('bar');
        $response->assertDontSeeHtml('<h2>foo</h2>');
    }

    /**
     * @test
     */
    public function test_assertDontSeeHtml_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo</h1>'));

        $this->expectFailureWithMessageContaining(
            'Response body contains [foo]',
            fn () => $response->assertDontSeeHtml('foo')
        );

        $this->expectFailureWithMessageContaining(
            'Response body contains [<h1>foo</h1>]',
            fn () => $response->assertDontSeeHtml('<h1>foo</h1>')
        );
    }

    /**
     * @test
     */
    public function test_assertSeeText_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo<b>bar</b></h1>'));

        $response->assertSeeText('foobar');
    }

    /**
     * @test
     */
    public function test_assertSeeText_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo<b>bar</b></h1>'));

        $this->expectFailureWithMessageContaining(
            'Response body does not contain [foobaz].',
            fn () => $response->assertSeeText('foobaz')
        );
    }

    /**
     * @test
     */
    public function test_assertDontSeeText_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo<b>bar</b></h1>'));

        $response->assertDontSeeText('foobaz');
    }

    /**
     * @test
     */
    public function test_assertDontSeeText_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('<h1>foo<b>bar</b></h1>'));

        $this->expectFailureWithMessageContaining(
            'Response body contains [foobar]',
            fn () => $response->assertDontSeeText('foobar')
        );
    }

    /**
     * @test
     */
    public function test_assertIsHtml_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $response->assertIsHtml();
    }

    /**
     * @test
     */
    public function test_assertIsHtml_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->json('foo'));

        $this->expectFailureWithMessageContaining(
            'Expected content-type [text/html; charset=UTF-8]',
            fn () => $response->assertIsHtml()
        );
    }

    /**
     * @test
     */
    public function test_assertIsJson_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->json('foo'));

        $response->assertIsJson();
    }

    /**
     * @test
     */
    public function test_assertIsJson_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $this->expectFailureWithMessageContaining(
            'Expected content-type [application/json]',
            fn () => $response->assertIsJson()
        );
    }

    /**
     * @test
     */
    public function test_assertBodyExact_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $response->assertBodyExact('foo');
    }

    /**
     * @test
     */
    public function test_assertBodyExact_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->html('foo'));

        $this->expectFailureWithMessageContaining(
            'Response body does not match expected [fooo]',
            fn () => $response->assertBodyExact('fooo')
        );
    }

    /**
     * @test
     */
    public function test_assertJsonExact_can_pass(): void
    {
        $response = new AssertableResponse($this->response_factory->json([
            'foo' => 'bar',
        ]));

        $response->assertExactJson([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function test_assertJsonExact_can_fail(): void
    {
        $response = new AssertableResponse($this->response_factory->json([
            'foo' => 'bar',
        ]));

        $this->expectFailureWithMessageContaining(
            'Response json body does not match expected [' . (string) json_encode([
                'foo' => 'baz',
            ]) . '].',
            fn () => $response->assertExactJson([
                'foo' => 'baz',
            ])
        );
    }

    /**
     * @test
     */
    public function test_getPsrResponse(): void
    {
        $response = new AssertableResponse($real = $this->response_factory->html('foo'));

        $this->assertSame($real, $response->getPsrResponse());
    }

    /**
     * @test
     */
    public function test_getHeader(): void
    {
        $response = new AssertableResponse($this->response_factory->createResponse()->withHeader('foo', 'bar'));

        $this->assertSame([
            'bar',
        ], $response->getHeader('foo'));
    }

    private function expectFailureWithMessageContaining(string $message, Closure $test): void
    {
        try {
            $test();
            $this->fail('Test assertion should have failed but it passed.');
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }

        $this->assertTrue(true);
    }
}
