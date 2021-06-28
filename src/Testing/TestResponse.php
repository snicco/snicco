<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use WPEmerge\Http\Psr7\Response;
    use PHPUnit\Framework\Assert as PHPUnit;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\VariableBag;
    use WPEmerge\Testing\Constraints\SeeInOrder;

    class TestResponse
    {

        /**
         * The response to delegate to.
         *
         * @var Response
         */
        public $psr_response;

        /**
         * @var string
         */
        protected $streamed_content;

        /**
         * @var VariableBag
         */
        private $headers;

        /**
         * @var mixed
         */
        private $status_code;

        public function __construct(Response $response)
        {
            $this->psr_response = $response;
            $this->headers = new VariableBag($this->psr_response->getHeaders());
            $this->streamed_content = (string) $this->psr_response->getBody();
            $this->status_code = $this->psr_response->getStatusCode();

        }


        /**
         * Assert that the response has a successful status code.
         *
         * @return $this
         */
        public function assertSuccessful() : TestResponse
        {
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
        public function assertOk() : TestResponse
        {
            PHPUnit::assertTrue(
                $this->isOk(),
                'Response status code ['.$this->getStatusCode().'] does not match expected 200 status code.'
            );

            return $this;
        }

        /**
         * Assert that the response has a 201 status code.
         *
         * @return $this
         */
        public function assertCreated() : TestResponse
        {
            $actual = $this->getStatusCode();

            PHPUnit::assertSame(
                201, $actual,
                "Response status code [{$actual}] does not match expected 201 status code."
            );

            return $this;
        }

        /**
         * Assert that the response has the given status code and no content.
         *
         * @param  int  $status
         * @return $this
         */
        public function assertNoContent($status = 204) : TestResponse
        {
            $this->assertStatus($status);

            PHPUnit::assertEmpty($this->streamed_content, 'Response content is not empty.');

            return $this;
        }

        /**
         * Assert that the response has a not found status code.
         *
         * @return $this
         */
        public function assertNotFound() : TestResponse
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
        public function assertForbidden() : TestResponse
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
        public function assertUnauthorized() : TestResponse
        {
            $actual = $this->getStatusCode();

            PHPUnit::assertSame(
                401, $actual,
                "Response status code [{$actual}] is not an unauthorized status code."
            );

            return $this;
        }

        /**
         * Assert that the response has the given status code.
         *
         * @param  int  $status
         * @return $this
         */
        public function assertStatus($status) : TestResponse
        {
            $actual = $this->getStatusCode();

            PHPUnit::assertSame(
                $actual, $status,
                "Expected status code {$status} but received {$actual}."
            );

            return $this;
        }

        /**
         * Assert whether the response is redirecting to a given URI.
         *
         * @param  string|null  $uri
         * @return $this
         */
        public function assertRedirect($uri = null) : TestResponse
        {
            PHPUnit::assertTrue(
                $this->isRedirect(), 'Response status code ['.$this->getStatusCode().'] is not a redirect status code.'
            );

            if (! is_null($uri)) {
                $this->assertLocation($uri);
            }

            return $this;
        }

        /**
         * Asserts that the response contains the given header and equals the optional value.
         *
         * @param  string  $headerName
         * @param  mixed  $value
         * @return $this
         */
        public function assertHeader($headerName, $value = null) : TestResponse
        {
            PHPUnit::assertTrue(
                $this->headers->has($headerName), "Header [{$headerName}] not present on response."
            );

            $actual = $this->headers->get($headerName);

            if (! is_null($value)) {
                PHPUnit::assertEquals(
                    $value, $this->headers->get($headerName),
                    "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
                );
            }

            return $this;
        }

        /**
         * Asserts that the response does not contain the given header.
         *
         * @param  string  $headerName
         * @return $this
         */
        public function assertHeaderMissing($headerName) : TestResponse
        {
            PHPUnit::assertFalse(
                $this->headers->has($headerName), "Unexpected header [{$headerName}] is present on response."
            );

            return $this;
        }

        /**
         * Assert that the current location header matches the given URI.
         *
         * @param  string  $uri
         * @return $this
         */
        public function assertLocation($uri) : TestResponse
        {
            PHPUnit::assertEquals(
                $uri, $this->headers->get('Location')
            );

            return $this;
        }

        /**
         * Assert that the given string or array of strings are contained within the response.
         *
         * @param  string|array  $value
         * @param  bool  $escape
         * @return $this
         */
        public function assertSee($value, $escape = true) : TestResponse
        {
            $value = Arr::wrap($value);

            $values = $escape ? array_map('esc_html', ($value)) : $value;

            foreach ($values as $value) {

                PHPUnit::assertStringContainsString( (string) $value, $this->streamed_content);

            }

            return $this;
        }

        /**
         * Assert that the given strings are contained in order within the response.
         *
         * @param  array  $values
         * @param  bool  $escape
         * @return $this
         */
        public function assertSeeInOrder(array $values, $escape = true)
        {
            $values = $escape ? array_map('esc_html', ($values)) : $values;

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
        public function assertSeeText($value, bool $escape = true) : TestResponse
        {
            $value = Arr::wrap($value);

            $values = $escape ? array_map('esc_html', ($value)) : $value;

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
         * @return $this
         */
        public function assertSeeTextInOrder(array $values, $escape = true) : TestResponse
        {
            $values = $escape ? array_map('esc_html', ($values)) : $values;

            PHPUnit::assertThat($values, new SeeInOrder(strip_tags($this->streamed_content)));

            return $this;
        }

        /**
         * Assert that the given string or array of strings are not contained within the response.
         *
         * @param  string|array  $value
         * @param  bool  $escape
         * @return $this
         */
        public function assertDontSee($value, $escape = true) : TestResponse
        {
            $value = Arr::wrap($value);

            $values = $escape ? array_map('esc_html', ($value)) : $value;

            foreach ($values as $value) {
                PHPUnit::assertStringNotContainsString((string) $value, $this->streamed_content);
            }

            return $this;
        }

        /**
         * Assert that the given string or array of strings are not contained within the response text.
         *
         * @param  string|array  $value
         * @param  bool  $escape
         * @return $this
         */
        public function assertDontSeeText($value, $escape = true) : TestResponse
        {
            $value = Arr::wrap($value);

            $values = $escape ? array_map('esc_html', ($value)) : $value;

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
         * @return $this
         */
        public function assertViewIs($value)
        {
            $this->ensureResponseHasView();

            PHPUnit::assertEquals($value, $this->original->name());

            return $this;
        }

        /**
         * Assert that the response view has a given piece of bound data.
         *
         * @param  string|array  $key
         * @param  mixed  $value
         * @return $this
         */
        public function assertViewHas($key, $value = null)
        {
            if (is_array($key)) {
                return $this->assertViewHasAll($key);
            }

            $this->ensureResponseHasView();

            if (is_null($value)) {
                PHPUnit::assertTrue(Arr::has($this->original->gatherData(), $key));
            } elseif ($value instanceof Closure) {
                PHPUnit::assertTrue($value(Arr::get($this->original->gatherData(), $key)));
            } elseif ($value instanceof Model) {
                PHPUnit::assertTrue($value->is(Arr::get($this->original->gatherData(), $key)));
            } else {
                PHPUnit::assertEquals($value, Arr::get($this->original->gatherData(), $key));
            }

            return $this;
        }

        /**
         * Assert that the response view has a given list of bound data.
         *
         * @param  array  $bindings
         * @return $this
         */
        public function assertViewHasAll(array $bindings)
        {
            foreach ($bindings as $key => $value) {
                if (is_int($key)) {
                    $this->assertViewHas($value);
                } else {
                    $this->assertViewHas($key, $value);
                }
            }

            return $this;
        }

        /**
         * Get a piece of data from the original view.
         *
         * @param  string  $key
         * @return mixed
         */
        public function viewData($key)
        {
            $this->ensureResponseHasView();

            return $this->original->gatherData()[$key];
        }

        /**
         * Assert that the response view is missing a piece of bound data.
         *
         * @param  string  $key
         * @return $this
         */
        public function assertViewMissing($key)
        {
            $this->ensureResponseHasView();

            PHPUnit::assertFalse(Arr::has($this->original->gatherData(), $key));

            return $this;
        }

        /**
         * Ensure that the response has a view as its original content.
         *
         * @return $this
         */
        protected function ensureResponseHasView()
        {
            if (! $this->responseHasView()) {
                return PHPUnit::fail('The response is not a view.');
            }

            return $this;
        }

        /**
         * Determine if the original response is a view.
         *
         * @return bool
         */
        protected function responseHasView()
        {
            return isset($this->original) && $this->original instanceof View;
        }

        /**
         * Assert that the session has a given value.
         *
         * @param  string|array  $key
         * @param  mixed  $value
         * @return $this
         */
        public function assertSessionHas($key, $value = null)
        {
            if (is_array($key)) {
                return $this->assertSessionHasAll($key);
            }

            if (is_null($value)) {
                PHPUnit::assertTrue(
                    $this->session()->has($key),
                    "Session is missing expected key [{$key}]."
                );
            } elseif ($value instanceof Closure) {
                PHPUnit::assertTrue($value($this->session()->get($key)));
            } else {
                PHPUnit::assertEquals($value, $this->session()->get($key));
            }

            return $this;
        }

        /**
         * Assert that the session has a given list of values.
         *
         * @param  array  $bindings
         * @return $this
         */
        public function assertSessionHasAll(array $bindings)
        {
            foreach ($bindings as $key => $value) {
                if (is_int($key)) {
                    $this->assertSessionHas($value);
                } else {
                    $this->assertSessionHas($key, $value);
                }
            }

            return $this;
        }

        /**
         * Assert that the session has a given value in the flashed input array.
         *
         * @param  string|array  $key
         * @param  mixed  $value
         * @return $this
         */
        public function assertSessionHasInput($key, $value = null)
        {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    if (is_int($k)) {
                        $this->assertSessionHasInput($v);
                    } else {
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
            } elseif ($value instanceof Closure) {
                PHPUnit::assertTrue($value($this->session()->getOldInput($key)));
            } else {
                PHPUnit::assertEquals($value, $this->session()->getOldInput($key));
            }

            return $this;
        }

        /**
         * Assert that the session has the given errors.
         *
         * @param  string|array  $keys
         * @param  mixed  $format
         * @param  string  $errorBag
         * @return $this
         */
        public function assertSessionHasErrors($keys = [], $format = null, $errorBag = 'default')
        {
            $this->assertSessionHas('errors');

            $keys = (array) $keys;

            $errors = $this->session()->get('errors')->getBag($errorBag);

            foreach ($keys as $key => $value) {
                if (is_int($key)) {
                    PHPUnit::assertTrue($errors->has($value), "Session missing error: $value");
                } else {
                    PHPUnit::assertContains(is_bool($value) ? (string) $value : $value, $errors->get($key, $format));
                }
            }

            return $this;
        }

        /**
         * Assert that the session is missing the given errors.
         *
         * @param  string|array  $keys
         * @param  string|null  $format
         * @param  string  $errorBag
         * @return $this
         */
        public function assertSessionDoesntHaveErrors($keys = [], $format = null, $errorBag = 'default')
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
                } else {
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
        public function assertSessionHasNoErrors()
        {
            $hasErrors = $this->session()->has('errors');

            $errors = $hasErrors ? $this->session()->get('errors')->all() : [];

            PHPUnit::assertFalse(
                $hasErrors,
                'Session has unexpected errors: '.PHP_EOL.PHP_EOL.
                json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

            return $this;
        }

        /**
         * Assert that the session has the given errors.
         *
         * @param  string  $errorBag
         * @param  string|array  $keys
         * @param  mixed  $format
         * @return $this
         */
        public function assertSessionHasErrorsIn($errorBag, $keys = [], $format = null)
        {
            return $this->assertSessionHasErrors($keys, $format, $errorBag);
        }

        /**
         * Assert that the session does not have a given key.
         *
         * @param  string|array  $key
         * @return $this
         */
        public function assertSessionMissing($key)
        {
            if (is_array($key)) {
                foreach ($key as $value) {
                    $this->assertSessionMissing($value);
                }
            } else {
                PHPUnit::assertFalse(
                    $this->session()->has($key),
                    "Session has unexpected key [{$key}]."
                );
            }

            return $this;
        }

        /**
         * Get the current session store.
         *
         * @return \Illuminate\Session\Store
         */
        protected function session()
        {
            return app('session.store');
        }

        /**
         * Dump the content from the response.
         *
         * @return $this
         */
        public function dump()
        {
            $content = $this->getContent();

            $json = json_decode($content);

            if (json_last_error() === JSON_ERROR_NONE) {
                $content = $json;
            }

            dump($content);

            return $this;
        }

        /**
         * Dump the headers from the response.
         *
         * @return $this
         */
        public function dumpHeaders()
        {
            dump($this->headers->all());

            return $this;
        }

        /**
         * Dump the session from the response.
         *
         * @param  string|array  $keys
         * @return $this
         */
        public function dumpSession($keys = [])
        {
            $keys = (array) $keys;

            if (empty($keys)) {
                dump($this->session()->all());
            } else {
                dump($this->session()->only($keys));
            }

            return $this;
        }

        /**
         * Get the streamed content from the response.
         *
         * @return string
         */
        public function streamedContent()
        {
            if (! is_null($this->streamed_content)) {
                return $this->streamed_content;
            }

            if (! $this->psr_response instanceof StreamedResponse) {
                PHPUnit::fail('The response is not a streamed response.');
            }

            ob_start();

            $this->sendContent();

            return $this->streamed_content = ob_get_clean();
        }

        /**
         * Dynamically access base response parameters.
         *
         * @param  string  $key
         * @return mixed
         */
        public function __get($key)
        {
            return $this->psr_response->{$key};
        }

        /**
         * Proxy isset() checks to the underlying base response.
         *
         * @param  string  $key
         * @return mixed
         */
        public function __isset($key)
        {
            return isset($this->psr_response->{$key});
        }

        /**
         * Determine if the given offset exists.
         *
         * @param  string  $offset
         * @return bool
         */
        public function offsetExists($offset)
        {
            return $this->responseHasView()
                ? isset($this->original->gatherData()[$offset])
                : isset($this->json()[$offset]);
        }

        /**
         * Get the value for a given offset.
         *
         * @param  string  $offset
         * @return mixed
         */
        public function offsetGet($offset)
        {
            return $this->responseHasView()
                ? $this->viewData($offset)
                : $this->json()[$offset];
        }

        /**
         * Set the value at the given offset.
         *
         * @param  string  $offset
         * @param  mixed  $value
         * @return void
         *
         * @throws \LogicException
         */
        public function offsetSet($offset, $value)
        {
            throw new LogicException('Response data may not be mutated using array access.');
        }

        /**
         * Unset the value at the given offset.
         *
         * @param  string  $offset
         * @return void
         *
         * @throws \LogicException
         */
        public function offsetUnset($offset)
        {
            throw new LogicException('Response data may not be mutated using array access.');
        }



        private function isSuccessful() : bool
        {
            return $this->psr_response->isSuccessful();
        }

        private function getStatusCode()
        {
            return $this->status_code;
        }

        private function isOk() : bool
        {
            return $this->psr_response->isOk();
        }

        private function isNotFound() : bool
        {
            return $this->psr_response->isNotFound();
        }

        private function isForbidden() : bool
        {
            return $this->psr_response->isForbidden();
        }

        private function isRedirect(string $location = null): bool
        {
            return $this->psr_response->isRedirect($location);
        }


    }
