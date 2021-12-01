<?php

declare(strict_types=1);

namespace Tests\Validation\integration;

use Snicco\Contracts\ExceptionHandler;
use Snicco\Session\SessionServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Validation\ValidationServiceProvider;
use Snicco\Validation\Exceptions\ValidationException;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class ValidationExceptionTest extends FrameworkTestCase
{
    
    /** @test */
    public function render_validation_exception_html()
    {
        $this->bootApp();
        $this->withRequest(
            $this->frontendRequest('POST', '/foobar')->withHeader('referer', '/foobar')
        );
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
        
        $response = $this->toTestResponse(
            $handler->toHttpResponse(
                $e,
                $this->request->withParsedBody([
                    'foo' => 'abcd',
                    'bar' => 'ab cd',
                ])
            )
        );
        $response->assertRedirect()->assertLocation('/foobar');
        
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
    public function a_named_error_bag_can_be_used()
    {
        $this->bootApp();
        $this->withRequest(
            $this->frontendRequest('POST', '/foobar')->withHeader('referer', '/foobar')
        );
        
        $e = ValidationException::withMessages([
            'foo' => [
                'foo must have a length between 5 and 10.',
            ],
        ])->setMessageBagName('login_form');
        
        $handler = $this->errorHandler();
        
        $response = $this->toTestResponse(
            $handler->toHttpResponse(
                $e,
                $this->request->withParsedBody([
                    'foo' => 'abcd',
                ])
            )
        );
        
        $response->assertRedirect();
        
        $response->assertSessionHasErrors([
            'foo' => 'foo must have a length between 5 and 10.',
        ], 'login_form');
        
        $response->assertSessionDoesntHaveErrors('foo');
    }
    
    /** @test */
    public function render_validation_exception_json()
    {
        $this->bootApp();
        $this->withRequest(
            $this->frontendRequest('POST', '/foobar')->withHeader('referer', '/foobar')
        );
        
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
        
        $response = $this->toTestResponse(
            $handler->toHttpResponse(
                $e,
                $this->request->withHeader('accept', 'application/json')
            )
        );
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
                ],
            ],
        ]);
    }
    
    /** @test */
    public function a_message_is_included_with_json_errors()
    {
        $this->bootApp();
        $this->withRequest(
            $this->frontendRequest('POST', '/foobar')->withHeader('referer', '/foobar')
        );
        
        $e = ValidationException::withMessages([
            'foo' => [
                'foo must have a length between 5 and 10.',
                'bar can not have whitespace',
            ],
            'bar' => [
                'bar must have a length between 5 and 10.',
                'bar can not have special chars',
            ],
        ])->withJsonMessageForUsers('This does not work like this.');
        
        $handler = $this->errorHandler();
        
        $response = $this->toTestResponse(
            $handler->toHttpResponse(
                $e,
                $this->request->withHeader('accept', 'application/json')
            )
        );
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
                ],
            ],
        ]);
    }
    
    /** @test */
    public function can_be_used_without_sessions()
    {
        $this->withRemovedProvider(SessionServiceProvider::class)->bootApp();
        
        $this->withRequest(
            $this->frontendRequest('POST', '/foobar')->withHeader('referer', '/foobar')
        );
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
        
        $response = $this->toTestResponse(
            $handler->toHttpResponse(
                $e,
                $this->request->withParsedBody([
                    'foo' => 'abcd',
                    'bar' => 'ab cd',
                ])
            )
        );
        $response->assertRedirect()->assertLocation('/foobar');
        
        $this->assertNull($response->session());
    }
    
    protected function packageProviders() :array
    {
        return [
            SessionServiceProvider::class,
            ValidationServiceProvider::class,
        ];
    }
    
    private function errorHandler() :ProductionExceptionHandler
    {
        return $this->app->resolve(ExceptionHandler::class);
    }
    
}