<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\StrArr\Str;

use function array_filter;
use function array_values;
use function count;
use function get_class;
use function json_encode;
use function ltrim;
use function sprintf;
use function strip_tags;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * @api
 */
final class AssertableResponse
{
    private Response $psr_response;

    private string $streamed_content;

    private int $status_code;

    public function __construct(Response $response)
    {
        $this->psr_response = $response;
        $this->streamed_content = (string) $this->psr_response->getBody();
        $this->status_code = $this->psr_response->getStatusCode();
    }

    public function body(): string
    {
        return $this->streamed_content;
    }

    public function assertDelegated(): self
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

    public function assertSuccessful(): AssertableResponse
    {
        $this->assertNotDelegated();

        PHPUnit::assertTrue(
            $this->psr_response->isSuccessful(),
            'Response status code [' . (string) $this->status_code . '] is not a success status code.'
        );

        return $this;
    }

    public function assertNotDelegated(): AssertableResponse
    {
        PHPUnit::assertNotInstanceOf(
            DelegatedResponse::class,
            $this->psr_response,
            'Expected response to not be delegated.'
        );
        return $this;
    }

    public function assertOk(): AssertableResponse
    {
        $this->assertStatus(200);

        return $this;
    }

    public function assertStatus(int $status): AssertableResponse
    {
        $this->assertNotDelegated();

        PHPUnit::assertEquals(
            $status,
            $this->status_code,
            "Expected response status code to be [$status].\nGot [$this->status_code]."
        );

        return $this;
    }

    public function assertCreated(): AssertableResponse
    {
        $this->assertStatus(201);
        return $this;
    }

    public function assertNoContent(): AssertableResponse
    {
        $this->assertStatus(204);

        PHPUnit::assertEquals(
            '',
            $this->streamed_content,
            'Response code matches expected [204] but the response body is not empty.'
        );

        return $this;
    }

    public function assertNotFound(): AssertableResponse
    {
        $this->assertStatus(404);
        return $this;
    }

    public function assertForbidden(): AssertableResponse
    {
        $this->assertStatus(403);

        return $this;
    }

    public function assertUnauthorized(): AssertableResponse
    {
        $this->assertStatus(401);
        return $this;
    }

    public function assertHeaderMissing(string $header_name): AssertableResponse
    {
        PHPUnit::assertFalse(
            $this->psr_response->hasHeader($header_name),
            "Header [$header_name] was not expected to be in the response.."
        );

        return $this;
    }

    public function assertRedirect(string $location = null, int $status = null): AssertableResponse
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

    public function assertLocation(string $location): AssertableResponse
    {
        $this->assertHeader('location');

        PHPUnit::assertEquals(
            $location,
            $actual = $this->psr_response->getHeaderLine('Location'),
            "Expected location header to be [$location].\nGot [$actual]."
        );

        return $this;
    }

    public function assertHeader(string $header_name, ?string $value = null): AssertableResponse
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

    /**
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function getAssertableCookie(string $cookie_name): AssertableCookie
    {
        $this->assertHeader('set-cookie');

        $header = $this->psr_response->getHeader('Set-Cookie');

        $headers = array_values(
            array_filter($header, function ($header) use ($cookie_name) {
                return Str::startsWith($header, $cookie_name);
            })
        );

        $count = count($headers);

        PHPUnit::assertNotEquals(
            0,
            $count,
            "Response does not have cookie matching name [$cookie_name]."
        );

        PHPUnit::assertSame(1, $count, "The cookie [$cookie_name] was sent [$count] times.");

        return new AssertableCookie($headers[0]);
    }

    public function assertRedirectPath(string $path, int $status = null): AssertableResponse
    {
        $this->assertIsRedirectStatus();

        if ($status) {
            $this->assertStatus($status);
        }

        $location = $this->psr_response->getHeaderLine('location');
        $path = '/' . ltrim($path, '/');

        PHPUnit::assertEquals(
            $path,
            parse_url($location, PHP_URL_PATH),
            "Redirect path [$path] does not match location header [$location]."
        );

        return $this;
    }

    public function assertSeeHtml(string $value): AssertableResponse
    {
        return $this->assertSee($value, false);
    }

    public function assertDontSeeHtml(string $value): AssertableResponse
    {
        return $this->assertDontSee($value, false);
    }

    public function assertSeeText(string $value): AssertableResponse
    {
        $this->assertSee($value);

        return $this;
    }

    public function assertDontSeeText(string $value): AssertableResponse
    {
        $this->assertDontSee($value);

        return $this;
    }

    public function assertBodyExact(string $expected): AssertableResponse
    {
        PHPUnit::assertSame(
            $expected,
            $this->streamed_content,
            "Response body does not match expected [$expected]."
        );
        return $this;
    }

    public function assertIsHtml(): AssertableResponse
    {
        $this->assertContentType('text/html');

        return $this;
    }

    public function assertContentType(string $expected, string $charset = 'UTF-8'): void
    {
        if (Str::startsWith($expected, 'text')) {
            $expected = trim($expected, ';') . '; charset=' . $charset;
        }

        PHPUnit::assertEquals(
            $expected,
            $actual = $this->psr_response->getHeaderLine('content-type'),
            "Expected content-type [$expected] but received [$actual]."
        );
    }

    public function assertExactJson(array $data): AssertableResponse
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

    public function assertIsJson(): AssertableResponse
    {
        $this->assertContentType('application/json');
        return $this;
    }

    public function getPsrResponse(): Response
    {
        return $this->psr_response;
    }

    public function getHeader(string $header): array
    {
        return $this->psr_response->getHeader($header);
    }

    private function assertIsRedirectStatus(): void
    {
        PHPUnit::assertTrue(
            $this->psr_response->isRedirection(),
            "Status code [$this->status_code] is not a redirection status code."
        );
    }

    private function assertSee(string $value, bool $text_only = true): AssertableResponse
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

    private function assertDontSee(string $value, bool $text_only = true): AssertableResponse
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
}
