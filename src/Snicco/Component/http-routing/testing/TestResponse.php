<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Snicco\Component\StrArr\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\Core\Utils\UrlPath;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Responses\DelegatedResponse;

use function trim;
use function count;
use function sprintf;
use function get_class;
use function strip_tags;
use function json_encode;
use function array_filter;

use const JSON_THROW_ON_ERROR;

/**
 * @api
 */
class TestResponse
{
    
    private Response $psr_response;
    private string   $streamed_content;
    private int      $status_code;
    
    public function __construct(Response $response)
    {
        $this->psr_response = $response;
        $this->streamed_content = (string) $this->psr_response->getBody();
        $this->status_code = $this->psr_response->getStatusCode();
    }
    
    final public function body() :string
    {
        return $this->streamed_content;
    }
    
    final public function assertDelegated() :self
    {
        PHPUnit::assertInstanceOf(
            DelegatedResponse::class,
            $this->psr_response,
            sprintf(
                "Expected response to be instance of [%s].\nGot [%s]",
                DelegatedResponse::class,
                get_class($this->psr_response)
            )
        );
        return $this;
    }
    
    final public function assertNotDelegated() :TestResponse
    {
        PHPUnit::assertNotInstanceOf(
            DelegatedResponse::class,
            $this->psr_response,
            "Expected response to not be delegated."
        );
        return $this;
    }
    
    final public function assertSuccessful() :TestResponse
    {
        $this->assertNotDelegated();
        
        PHPUnit::assertTrue(
            $this->psr_response->isSuccessful(),
            'Response status code ['.$this->status_code.'] is not a success status code.'
        );
        
        return $this;
    }
    
    final public function assertOk() :TestResponse
    {
        $this->assertStatus(200);
        
        return $this;
    }
    
    final public function assertCreated() :TestResponse
    {
        $this->assertStatus(201);
        return $this;
    }
    
    final public function assertNoContent() :TestResponse
    {
        $this->assertStatus(204);
        
        PHPUnit::assertEquals(
            '',
            $this->streamed_content,
            'Response code matches expected [204] but the response body is not empty.'
        );
        
        return $this;
    }
    
    final public function assertStatus(int $status) :TestResponse
    {
        $this->assertNotDelegated();
        
        PHPUnit::assertEquals(
            $status,
            $this->status_code,
            "Expected response status code to be [$status].\nGot [$this->status_code]."
        );
        
        return $this;
    }
    
    final public function assertNotFound() :TestResponse
    {
        $this->assertStatus(404);
        return $this;
    }
    
    final public function assertForbidden() :TestResponse
    {
        $this->assertStatus(403);
        
        return $this;
    }
    
    final public function assertUnauthorized() :TestResponse
    {
        $this->assertStatus(401);
        return $this;
    }
    
    final public function assertHeader(string $header_name, $value = null) :TestResponse
    {
        PHPUnit::assertTrue(
            $this->psr_response->hasHeader($header_name),
            "Response does not have header [$header_name]."
        );
        
        if (null === $value) {
            return $this;
        }
        
        $actual = $this->psr_response->getHeaderLine($header_name);
        
        PHPUnit::assertEquals(
            $value,
            $actual,
            "Value [$actual] for header [$header_name] does not match [$value]."
        );
        
        return $this;
    }
    
    final public function assertHeaderMissing(string $header_name) :TestResponse
    {
        PHPUnit::assertFalse(
            $this->psr_response->hasHeader($header_name),
            "Header [$header_name] was not expected to be in the response.."
        );
        
        return $this;
    }
    
    final public function assertLocation(string $location) :TestResponse
    {
        $this->assertHeader('location');
        
        PHPUnit::assertEquals(
            $location,
            $actual = $this->psr_response->getHeaderLine('Location'),
            "Expected location header to be [$location].\nGot [$actual]."
        );
        
        return $this;
    }
    
    final public function assertRedirect(string $location = null, int $status = null) :TestResponse
    {
        $this->assertIsRedirectStatus();
        
        if (null === $location) {
            return $this;
        }
        
        $this->assertLocation($location);
        
        if (null === $status) {
            return $this;
        }
        
        $this->assertStatus($status);
        
        return $this;
    }
    
    final public function getAssertableCookie(string $cookie_name) :AssertableCookie
    {
        $this->assertHeader('set-cookie');
        
        $header = $this->psr_response->getHeader('Set-Cookie');
        
        $headers = array_filter($header, function ($header) use ($cookie_name) {
            return Str::startsWith($header, $cookie_name);
        });
        
        $count = count($headers);
        
        PHPUnit::assertNotEquals(
            0,
            $count,
            "Response does not have cookie matching name [$cookie_name]."
        );
        
        PHPUnit::assertSame(1, $count, "The cookie [$cookie_name] was sent [$count] times.");
        
        return new AssertableCookie($headers[0]);
    }
    
    final public function assertRedirectPath(string $path, int $status = null) :TestResponse
    {
        $this->assertIsRedirectStatus();
        
        if ($status) {
            $this->assertStatus($status);
        }
        
        $location = $this->psr_response->getHeaderLine('location');
        $path = UrlPath::fromString($path);
        
        PHPUnit::assertEquals(
            $path->asString(),
            parse_url($location, PHP_URL_PATH),
            "Redirect path [$path] does not match location header [$location]."
        );
        
        return $this;
    }
    
    final public function assertContentType(string $expected, string $charset = 'UTF-8')
    {
        if (Str::startsWith($expected, 'text')) {
            $expected = trim($expected, ';').'; charset='.$charset;
        }
        
        PHPUnit::assertEquals(
            $expected,
            $actual = $this->psr_response->getHeaderLine('content-type'),
            "Expected content-type [$expected] but received [$actual]."
        );
    }
    
    final public function assertSeeHtml(string $value) :TestResponse
    {
        return $this->assertSee($value, false);
    }
    
    final public function assertDontSeeHtml(string $value) :TestResponse
    {
        return $this->assertDontSee($value, false);
    }
    
    final public function assertSeeText(string $value) :TestResponse
    {
        $this->assertSee($value);
        
        return $this;
    }
    
    final public function assertDontSeeText(string $value) :TestResponse
    {
        $this->assertDontSee($value);
        
        return $this;
    }
    
    final public function assertBodyExact(string $expected) :TestResponse
    {
        PHPUnit::assertSame(
            $expected,
            $this->streamed_content,
            "Response body does not match expected [$expected]."
        );
        return $this;
    }
    
    final public function assertIsHtml() :TestResponse
    {
        $this->assertContentType('text/html');
        
        return $this;
    }
    
    final public function assertExactJson(array $data) :TestResponse
    {
        $this->assertIsJson();
        $expected = json_encode($data, JSON_THROW_ON_ERROR);
        
        PHPUnit::assertSame(
            $expected,
            $this->streamed_content,
            "Response json body does not match expected [$expected]."
        );
        
        return $this;
    }
    
    final public function assertIsJson() :TestResponse
    {
        $this->assertContentType('application/json');
        return $this;
    }
    
    final public function getPsrResponse() :Response
    {
        return $this->psr_response;
    }
    
    private function assertSee(string $value, bool $text_only = true) :TestResponse
    {
        $compare_to = $text_only ?
            strip_tags($this->streamed_content)
            : $this->streamed_content;
        
        PHPUnit::assertStringContainsString(
            $value,
            $compare_to,
            "Response body does not contain [$value]."
        );
        
        return $this;
    }
    
    private function assertDontSee(string $value, bool $text_only = true) :TestResponse
    {
        $compare_to = $text_only ?
            strip_tags($this->streamed_content)
            : $this->streamed_content;
        
        PHPUnit::assertStringNotContainsString(
            $value,
            $compare_to,
            "Response body contains [$value]."
        );
        
        return $this;
    }
    
    private function assertIsRedirectStatus() :void
    {
        PHPUnit::assertTrue(
            $this->psr_response->isRedirection(),
            "Status code [$this->status_code] is not a redirection status code."
        );
    }
    
}