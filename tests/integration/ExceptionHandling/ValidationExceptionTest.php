<?php


    declare(strict_types = 1);


    namespace Tests\integration\ExceptionHandling;

    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use BetterWP\Contracts\ErrorHandlerInterface;
    use BetterWP\ExceptionHandling\ProductionErrorHandler;
    use BetterWP\Session\SessionServiceProvider;
    use BetterWP\Testing\TestResponse;
    use BetterWP\Validation\Exceptions\ValidationException;

    class ValidationExceptionTest extends TestCase
    {

        public function packageProviders() : array
        {

            return [
                SessionServiceProvider::class,
            ];
        }

        private function errorHandler() : ProductionErrorHandler
        {

            return $this->app->resolve(ErrorHandlerInterface::class);
        }

        /** @test */
        public function render_validation_exception_html()
        {

            $this->withRequest(TestRequest::from('POST', 'foo'));

            $e = ValidationException::withMessages([
                'foo' => [
                    'foo must have a length between 5 and 10.',
                    'bar can not have whitespace',
                ],
                'bar' => [
                    'bar must have a length between 5 and 10.',
                    'bar can not have special chars',
                ],
            ]);

            $handler = $this->errorHandler();

            $response = $this->toTestResponse($handler->transformToResponse($e, $this->request->withParsedBody([
                'foo' => 'abcd',
                'bar' => 'ab cd'
            ])));
            $response->assertRedirect();

            $response->assertSessionHasErrors([
                'foo' => 'foo must have a length between 5 and 10.',
            ]);
            $response->assertSessionHasErrors([
                'foo' => 'bar can not have whitespace',
            ]);
            $response->assertSessionHasErrors([
                'bar' => 'bar must have a length between 5 and 10.',
            ]);
            $response->assertSessionHasErrors([
                'bar' => 'bar can not have special chars',
            ]);
            $response->assertSessionHasInput(['foo' => 'abcd']);
            $response->assertSessionHasInput(['bar' => 'ab cd']);

        }

        /** @test */
        public function a_named_error_bag_can_be_used () {

            $this->withRequest(TestRequest::from('POST', 'foo'));

            $e = ValidationException::withMessages([
                'foo' => [
                    'foo must have a length between 5 and 10.',
                ],
            ])->setMessageBagName('login_form');

            $handler = $this->errorHandler();

            $response = $this->toTestResponse($handler->transformToResponse($e, $this->request->withParsedBody([
                'foo' => 'abcd',
            ])));

            $response->assertRedirect();

            $response->assertSessionHasErrors([
                'foo' => 'foo must have a length between 5 and 10.',
            ], 'login_form');

            $response->assertSessionDoesntHaveErrors('foo', 'default');


        }

        /** @test */
        public function render_validation_exception_json () {

            $this->withRequest(TestRequest::from('POST', 'foo'));

            $e = ValidationException::withMessages([
                'foo' => [
                    'foo must have a length between 5 and 10.',
                    'bar can not have whitespace',
                ],
                'bar' => [
                    'bar must have a length between 5 and 10.',
                    'bar can not have special chars',
                ],
            ]);

            $handler = $this->errorHandler();

            $response = $this->toTestResponse($handler->transformToResponse($e, $this->request->withHeader('accept', 'application/json')));
            $response->assertStatus(400);
            $response->assertIsJson();
            $response->assertExactJson([
                'message' => 'We could not process your request.',
                'errors' => [
                    'foo' => [
                        'foo must have a length between 5 and 10.',
                        'bar can not have whitespace',
                    ],
                    'bar' => [
                        'bar must have a length between 5 and 10.',
                        'bar can not have special chars',
                    ]
                ]
            ]);

        }

        /** @test */
        public function a_message_is_included_with_json_errors () {

            $this->withRequest(TestRequest::from('POST', 'foo'));

            $e = ValidationException::withMessages([
                'foo' => [
                    'foo must have a length between 5 and 10.',
                    'bar can not have whitespace',
                ],
                'bar' => [
                    'bar must have a length between 5 and 10.',
                    'bar can not have special chars',
                ],
            ])->setJsonMessage('This does not work like this.');

            $handler = $this->errorHandler();

            $response = $this->toTestResponse($handler->transformToResponse($e, $this->request->withHeader('accept', 'application/json')));
            $response->assertStatus(400);
            $response->assertIsJson();
            $response->assertExactJson([
                'message' => 'This does not work like this.',
                'errors' => [
                    'foo' => [
                        'foo must have a length between 5 and 10.',
                        'bar can not have whitespace',
                    ],
                    'bar' => [
                        'bar must have a length between 5 and 10.',
                        'bar can not have special chars',
                    ]
                ]
            ]);

        }

    }