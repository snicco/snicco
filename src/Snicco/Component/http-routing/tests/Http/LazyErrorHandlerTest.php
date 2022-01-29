<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use Mockery;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\LazyHttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

final class LazyErrorHandlerTest extends TestCase
{
    
    use CreateTestPsrContainer;
    use CreateTestPsr17Factories;
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function the_lazy_error_handler_behaves_the_same_as_the_real_error_handler_it_proxies_to()
    {
        $c = $this->createContainer();
        $c[HttpErrorHandlerInterface::class] = Mockery::mock(HttpErrorHandlerInterface::class);
        $lazy_handler = new LazyHttpErrorHandler($c);
        
        $this->assertInstanceOf(HttpErrorHandlerInterface::class, $lazy_handler);
    }
    
    /** @test */
    public function an_exception_is_thrown_if_the_lazy_error_handler_doesnt_have_the_http_error_handler_interface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "The psr container needs a service for id [".HttpErrorHandlerInterface::class."]."
        );
        
        $lazy_handler = new LazyHttpErrorHandler($c = $this->createContainer());
    }
    
    /** @test */
    public function calls_are_proxies_to_the_real_handler()
    {
        $count = 0;
        $c = $this->createContainer();
        
        $mock = Mockery::mock(HttpErrorHandlerInterface::class);
        
        $c->singleton(HttpErrorHandlerInterface::class, function () use (&$count, $mock) {
            $count++;
            return $mock;
        });
        
        $lazy_handler = new LazyHttpErrorHandler($c);
        
        $mock->shouldReceive('handle')->once()->andReturnUsing(function () {
            $response = $this->psrResponseFactory()->createResponse(500);
            $response->getBody()->write('foo error');
            return $response;
        });
        
        $response = $lazy_handler->handle(
            new Exception('secret stuff'),
            $this->psrServerRequestFactory()->createServerRequest('GET', '/foo')
        );
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('foo error', (string) $response->getBody());
    }
    
}