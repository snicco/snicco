<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Closure;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Response;
use Snicco\Support\Repository;
use Snicco\Routing\UrlGenerator;
use Snicco\Application\Application;
use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Http\Responses\NullResponse;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Testing\Constraints\SeeInOrder;
use Snicco\Http\Responses\DelegatedResponse;
use Snicco\Testing\Assertable\AssertableCookie;

class TestResponse
{
    
    public Response        $psr_response;
    protected string       $streamed_content;
    private Repository     $headers;
    private int            $status_code;
    private ?ViewInterface $view    = null;
    private ?Session       $session = null;
    private Application    $app;
    
    public function __construct(Response $response)
    {
        $this->psr_response = $response;
        $this->headers = new Repository($this->psr_response->getHeaders());
        $this->streamed_content = (string) $this->psr_response->getBody();
        $this->status_code = $this->psr_response->getStatusCode();
    }
    
    public function __call($method, $args)
    {
        return $this->psr_response->{$method}(...$args);
    }
    
    public function setSession(Session $session)
    {
        $this->session = $session;
    }
    
    public function setRenderedView(ViewInterface $rendered_view)
    {
        $this->view = $rendered_view;
    }
    
    public function setApp(Application $app)
    {
        $this->app = $app;
    }
    
    public function body() :string
    {
        return $this->streamed_content;
    }
    
    public function assertNullResponse() :TestResponse
    {
        PHPUnit::assertInstanceOf(
            NullResponse::class,
            $this->psr_response,
            "A response was returned unexpectedly."
        );
        
        return $this;
    }
    
    public function assertDelegatedToWordPress() :TestResponse
    {
        return $this->assertInstance(DelegatedResponse::class);
    }
    
    public function assertNotDelegatedToWordPress() :TestResponse
    {
        return $this->assertNotInstance(DelegatedResponse::class);
    }
    
    public function assertInstance(string $class) :TestResponse
    {
        PHPUnit::assertInstanceOf($class, $this->psr_response);
        
        return $this;
    }
    
    /**
     * Assert that the response has a successful status code.
     *
     * @return $this
     */
    public function assertSuccessful() :TestResponse
    {
        $this->assertNotDelegatedToWordPress();
        
        PHPUnit::assertTrue(
            $this->isSuccessful(),
            'Response status code ['.$this->getStatusCode().'] is not a successful status code.'
        );
        
        return $this;
    }
    
    /**
     * Assert that the response has a 200 status code.
     *
     * @return $this
     */
    public function assertOk() :TestResponse
    {
        $this->assertNotDelegatedToWordPress();
        
        PHPUnit::assertTrue(
            $this->isOk(),
            'Response status code ['
            .$this->getStatusCode()
            .'] does not match expected 200 status code.'
        );
        
        return $this;
    }
    
    /**
     * Assert that the response has a 201 status code.
     *
     * @return $this
     */
    public function assertCreated() :TestResponse
    {
        $actual = $this->getStatusCode();
        
        PHPUnit::assertSame(
            201,
            $actual,
            "Response status code [{$actual}] does not match expected 201 status code."
        );
        
        return $this;
    }
    
    /**
     * Assert that the response has the given status code and no content.
     *
     * @param  int  $status
     *
     * @return $this
     */
    public function assertNoContent($status = 204) :TestResponse
    {
        $this->assertStatus($status);
        
        PHPUnit::assertEmpty($this->streamed_content, 'Response content is not empty.');
        
        return $this;
    }
    
    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     *
     * @return $this
     */
    public function assertStatus($status) :TestResponse
    {
        $this->assertNotNullResponse();
        $this->assertNotDelegatedToWordPress();
        
        $actual = $this->getStatusCode();
        
        PHPUnit::assertSame(
            $actual,
            $status,
            "Expected status code {$status} but received {$actual}."
        );
        
        return $this;
    }
    
    public function assertNotNullResponse()
    {
        PHPUnit::assertNotInstanceOf(NullResponse::class, $this->psr_response);
        
        return $this;
    }
    
    /**
     * Assert that the response has a not found status code.
     *
     * @return $this
     */
    public function assertNotFound() :TestResponse
    {
        PHPUnit::assertTrue(
            $this->isNotFound(),
            'Response status code ['.$this->getStatusCode().'] is not a not found status code.'
        );
        
        return $this;
    }
    
    /**
     * Assert that the response has a forbidden status code.
     *
     * @return $this
     */
    public function assertForbidden() :TestResponse
    {
        PHPUnit::assertTrue(
            $this->isForbidden(),
            'Response status code ['.$this->getStatusCode().'] is not a forbidden status code.'
        );
        
        return $this;
    }
    
    /**
     * Assert that the response has an unauthorized status code.
     *
     * @return $this
     */
    public function assertUnauthorized() :TestResponse
    {
        $actual = $this->getStatusCode();
        
        PHPUnit::assertSame(
            401,
            $actual,
            "Response status code [{$actual}] is not an unauthorized status code."
        );
        
        return $this;
    }
    
    public function assertRedirectPath(string $path, int $status = null) :TestResponse
    {
        PHPUnit::assertTrue(
            $this->isRedirect(),
            'Response status code ['.$this->getStatusCode().'] is not a redirect status code.'
        );
        
        if ($status) {
            $this->assertStatus($status);
        }
        
        $location = $this->psr_response->getHeaderLine('location');
        $path = Url::addLeading($path);
        PHPUnit::assertSame($path, parse_url($location, PHP_URL_PATH));
        
        return $this;
    }
    
    public function assertRedirectToRoute(string $route, int $status_code = null) :TestResponse
    {
        /** @var UrlGenerator $url */
        $url = $this->app->resolve(UrlGenerator::class);
        
        $this->assertRedirect();
        
        PHPUnit::assertSame(
            $expected = $url->toRoute($route),
            $actual = $this->psr_response->getHeaderLine('Location'),
            "The url for the route [$route] is [$expected]. Redirected to [$actual]. "
        );
        
        if ($status_code) {
            $this->assertStatus($status_code);
        }
        
        return $this;
    }
    
    /**
     * Assert whether the response is redirecting to a given URI.
     *
     * @param  string|null  $uri
     * @param  int|null  $status
     *
     * @return $this
     */
    public function assertRedirect(string $uri = null, int $status = null) :TestResponse
    {
        PHPUnit::assertTrue(
            $this->isRedirect(),
            'Response status code ['.$this->getStatusCode().'] is not a redirect status code.'
        );
        
        if ( ! is_null($uri)) {
            $this->assertLocation($uri);
        }
        
        if ( ! is_null($status)) {
            $this->assertStatus($status);
        }
        
        return $this;
    }
    
    /**
     * Assert that the current location header matches the given URI.
     *
     * @param  string  $uri
     *
     * @return $this
     */
    public function assertLocation($uri) :TestResponse
    {
        PHPUnit::assertEquals(
            $uri,
            $this->headers->get('Location')[0]
        );
        
        return $this;
    }
    
    public function cookie(string $cookie_name)
    {
        $this->assertHeader('Set-Cookie');
        
        $header = $this->psr_response->getHeader('Set-Cookie');
        $headers = array_filter($header, function ($header) use ($cookie_name) {
            return Str::startsWith($header, $cookie_name);
        });
        $count = count($headers);
        if ($count > 1) {
            PHPUnit::fail(
                "The cookie [$cookie_name] was set [$count} times on the response.]"
            );
        }
        
        return new AssertableCookie($headers[0]);
    }
    
    /**
     * Asserts that the response contains the given header and equals the optional value.
     *
     * @param  string  $header_name
     * @param  mixed  $value
     *
     * @return $this
     */
    public function assertHeader(string $header_name, $value = null) :TestResponse
    {
        PHPUnit::assertTrue(
            $this->psr_response->hasHeader($header_name),
            "Header [{$header_name}] not present on response."
        );
        
        $actual = $this->psr_response->getHeaderLine($header_name);
        
        if ( ! is_null($value)) {
            PHPUnit::assertEquals(
                $value,
                $actual,
                "Header [{$header_name}] was found, but value [{$actual}] does not match [{$value}]."
            );
        }
        
        return $this;
    }
    
    /**
     * Asserts that the response does not contain the given header.
     *
     * @param  string  $headerName
     *
     * @return $this
     */
    public function assertHeaderMissing($headerName) :TestResponse
    {
        PHPUnit::assertFalse(
            $this->headers->has($headerName),
            "Unexpected header [{$headerName}] is present on response."
        );
        
        return $this;
    }
    
    /**
     * @param  string|array  $value
     */
    public function assertSeeHtml($value) :TestResponse
    {
        return $this->assertSee($value, false);
    }
    
    /**
     * @param  string|array  $value
     */
    public function assertDontSeeHtml($value) :TestResponse
    {
        return $this->assertDontSee($value, false);
    }
    
    /**
     * Assert that the given string or array of strings are contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     *
     * @return $this
     */
    public function assertSee($value, $escape = true) :TestResponse
    {
        $value = Arr::wrap($value);
        
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES, 'UTF-8'), $value)
            : $value;
        
        foreach ($values as $value) {
            PHPUnit::assertStringContainsString((string) $value, $this->streamed_content);
        }
        
        return $this;
    }
    
    /**
     * Assert that the given strings are contained in order within the response.
     *
     * @param  array  $values
     * @param  bool  $escape
     *
     * @return $this
     */
    public function assertSeeInOrder(array $values, $escape = true)
    {
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES), $values)
            : $values;
        
        PHPUnit::assertThat($values, new SeeInOrder($this->streamed_content));
        
        return $this;
    }
    
    /**
     * Assert that the given string or array of strings are contained within the response text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     *
     * @return TestResponse
     */
    public function assertSeeText($value, bool $escape = true) :TestResponse
    {
        $value = Arr::wrap($value);
        
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES,), $value)
            : $value;
        
        tap(strip_tags($this->streamed_content), function ($content) use ($values) {
            foreach ($values as $value) {
                PHPUnit::assertStringContainsString((string) $value, $content);
            }
        });
        
        return $this;
    }
    
    /**
     * Assert that the given strings are contained in order within the response text.
     *
     * @param  array  $values
     * @param  bool  $escape
     *
     * @return $this
     */
    public function assertSeeTextInOrder(array $values, $escape = true) :TestResponse
    {
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES,), $values)
            : $values;
        
        PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->streamed_content)));
        
        return $this;
    }
    
    /**
     * Assert that the given string or array of strings are not contained within the response.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     *
     * @return $this
     */
    public function assertDontSee($value, $escape = true) :TestResponse
    {
        $value = Arr::wrap($value);
        
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES,), $value)
            : $value;
        
        foreach ($values as $value) {
            PHPUnit::assertStringNotContainsString((string) $value, $this->streamed_content);
        }
        
        return $this;
    }
    
    /**
     * Assert that the given string or array of strings are not contained within the response
     * text.
     *
     * @param  string|array  $value
     * @param  bool  $escape
     *
     * @return $this
     */
    public function assertDontSeeText($value, $escape = true) :TestResponse
    {
        $value = Arr::wrap($value);
        
        $values = $escape
            ? array_map(fn($val) => htmlspecialchars($val, ENT_QUOTES,), $value)
            : $value;
        
        tap(strip_tags($this->streamed_content), function ($content) use ($values) {
            foreach ($values as $value) {
                PHPUnit::assertStringNotContainsString((string) $value, $content);
            }
        });
        
        return $this;
    }
    
    /**
     * Assert that the response view equals the given value.
     *
     * @param  string  $value
     *
     * @return $this
     */
    public function assertViewIs($value) :TestResponse
    {
        $this->ensureResponseHasView();
        
        PHPUnit::assertEquals($value, $this->view->name());
        
        return $this;
    }
    
    /**
     * Assert that the response view has a given piece of bound data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     *
     * @return $this
     */
    public function assertViewHas($key, $value = null) :TestResponse
    {
        if (is_array($key)) {
            return $this->assertViewHasAll($key);
        }
        
        $this->ensureResponseHasView();
        
        if (is_null($value)) {
            PHPUnit::assertTrue(Arr::has($this->view->context(), $key));
        }
        elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value(Arr::get($this->view->context(), $key)));
        }
        else {
            PHPUnit::assertEquals($value, Arr::get($this->view->context(), $key));
        }
        
        return $this;
    }
    
    /**
     * Assert that the response view has a given list of bound data.
     *
     * @param  array  $bindings
     *
     * @return $this
     */
    public function assertViewHasAll(array $bindings) :TestResponse
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value);
            }
            else {
                $this->assertViewHas($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Assert that the response view is missing a piece of bound data.
     *
     * @param  string  $key
     *
     * @return $this
     */
    public function assertViewMissing(string $key) :TestResponse
    {
        $this->ensureResponseHasView();
        
        PHPUnit::assertFalse(Arr::has($this->view->context(), $key));
        
        return $this;
    }
    
    /**
     * Assert that the session has a given value in the flashed input array.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     *
     * @return $this
     */
    public function assertSessionHasInput($key, $value = null) :TestResponse
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_int($k)) {
                    $this->assertSessionHasInput($v);
                }
                else {
                    $this->assertSessionHasInput($k, $v);
                }
            }
            
            return $this;
        }
        
        if (is_null($value)) {
            PHPUnit::assertTrue(
                $this->session()->hasOldInput($key),
                "Session is missing expected key [{$key}]."
            );
        }
        elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->session()->getOldInput($key)));
        }
        else {
            PHPUnit::assertEquals($value, $this->session()->getOldInput($key));
        }
        
        return $this;
    }
    
    public function session() :?Session
    {
        return $this->session;
    }
    
    /**
     * Assert that the session is missing the given errors.
     *
     * @param  string|array  $keys
     * @param  string  $errorBag
     * @param  string|null  $format
     *
     * @return $this
     */
    public function assertSessionDoesntHaveErrors($keys = [], $errorBag = 'default', $format = null) :TestResponse
    {
        $keys = (array) $keys;
        
        if (empty($keys)) {
            return $this->assertSessionHasNoErrors();
        }
        
        if (is_null($this->session()->get('errors'))) {
            PHPUnit::assertTrue(true);
            
            return $this;
        }
        
        $errors = $this->session()->get('errors')->getBag($errorBag);
        
        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertFalse($errors->has($value), "Session has unexpected error: $value");
            }
            else {
                PHPUnit::assertNotContains($value, $errors->get($key, $format));
            }
        }
        
        return $this;
    }
    
    /**
     * Assert that the session has no errors.
     *
     * @return $this
     */
    public function assertSessionHasNoErrors() :TestResponse
    {
        $hasErrors = $this->session()->has('errors');
        
        $errors = $hasErrors ? $this->session()->get('errors')->all() : [];
        
        PHPUnit::assertFalse(
            $hasErrors,
            'Session has unexpected errors: '.PHP_EOL.PHP_EOL.
            json_encode(
                $errors,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
        
        return $this;
    }
    
    /**
     * Assert that the session has the given errors.
     *
     * @param  string  $errorBag
     * @param  string|array  $keys
     * @param  mixed  $format
     *
     * @return $this
     */
    public function assertSessionHasErrorsIn($errorBag, $keys = [], $format = null) :TestResponse
    {
        return $this->assertSessionHasErrors($keys, $format, $errorBag);
    }
    
    /**
     * Assert that the session has the given errors.
     *
     * @param  string|array  $keys
     * @param  string  $errorBag
     * @param  mixed  $format
     *
     * @return $this
     */
    public function assertSessionHasErrors($keys = [], $errorBag = 'default', $format = null) :TestResponse
    {
        $this->assertSessionHas('errors');
        
        $keys = (array) $keys;
        
        $errors = $this->session()->get('errors')->getBag($errorBag);
        
        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
            }
            else {
                PHPUnit::assertContains(
                    is_bool($value) ? (string) $value : $value,
                    $errors->get($key, $format),
                    "Message [$value] not found for key [$key]."
                );
            }
        }
        
        return $this;
    }
    
    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     *
     * @return $this
     */
    public function assertSessionHas($key, $value = null) :TestResponse
    {
        if (is_array($key)) {
            return $this->assertSessionHasAll($key);
        }
        
        if (is_null($value)) {
            PHPUnit::assertTrue(
                $this->session()->has($key),
                "Session is missing expected key [{$key}]."
            );
        }
        elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->session()->get($key)));
        }
        else {
            PHPUnit::assertEquals($value, $this->session()->get($key));
        }
        
        return $this;
    }
    
    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     *
     * @return $this
     */
    public function assertSessionHasAll(array $bindings) :TestResponse
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);
            }
            else {
                $this->assertSessionHas($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     *
     * @return $this
     */
    public function assertSessionMissing($key) :TestResponse
    {
        if (is_array($key)) {
            foreach ($key as $value) {
                $this->assertSessionMissing($value);
            }
        }
        else {
            PHPUnit::assertFalse(
                $this->session()->has($key),
                "Session has unexpected key [{$key}]."
            );
        }
        
        return $this;
    }
    
    public function assertIsHtml() :TestResponse
    {
        $this->assertContentType('text/html');
        
        return $this;
    }
    
    public function assertContentType(string $expected, string $charset = 'UTF-8')
    {
        if (Str::startsWith($expected, 'text')) {
            $expected = trim($expected, ';').'; charset='.$charset;
        }
        
        PHPUnit::assertSame(
            $expected,
            $actual = $this->psr_response->getHeaderLine('Content-Type'),
            "Expected content type {$expected} but received {$actual}."
        );
    }
    
    public function assertExactJson(array $data) :TestResponse
    {
        $this->assertIsJson();
        $actual = json_decode($this->streamed_content, true);
        
        PHPUnit::assertSame($data, $actual, 'Incorrect json response.');
        
        return $this;
    }
    
    public function assertIsJson() :TestResponse
    {
        $this->assertContentType('application/json');
        
        return $this;
    }
    
    private function isSuccessful() :bool
    {
        return $this->psr_response->isSuccessful();
    }
    
    private function getStatusCode()
    {
        return $this->status_code;
    }
    
    private function isOk() :bool
    {
        return $this->psr_response->isOk();
    }
    
    private function isNotFound() :bool
    {
        return $this->psr_response->isNotFound();
    }
    
    private function isForbidden() :bool
    {
        return $this->psr_response->isForbidden();
    }
    
    private function isRedirect(string $location = null) :bool
    {
        return $this->psr_response->isRedirect($location);
    }
    
    /**
     * Ensure that the response has a view as its original content.
     *
     * @return $this
     */
    private function ensureResponseHasView() :TestResponse
    {
        if ( ! $this->view instanceof ViewInterface) {
            PHPUnit::fail('The response is not a view.');
        }
        
        return $this;
    }
    
    private function assertNotInstance(string $class) :TestResponse
    {
        PHPUnit::assertNotInstanceOf($class, $this->psr_response);
        return $this;
    }
    
}
