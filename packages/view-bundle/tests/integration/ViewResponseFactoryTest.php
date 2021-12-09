<?php

declare(strict_types=1);

namespace Tests\ViewBundle\integration;

use Snicco\Core\Http\Psr7\Response;
use Snicco\ViewBundle\ViewResponseFactory;
use Snicco\ViewBundle\ViewServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;

final class ViewResponseFactoryTest extends FrameworkTestCase
{
    
    /**
     * @var ViewResponseFactory
     */
    private $factory;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $this->factory = $this->app->resolve(ViewResponseFactory::class);
        });
        
        parent::setUp();
        $this->bootApp();
    }
    
    public function testView()
    {
        $response = $this->factory->view(
            'view-with-context',
            ['world' => 'World'],
            205,
            ['header1' => 'foo']
        );
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(205, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));
        $this->assertSame('Hello World', (string) $response->getBody());
        $this->assertSame('foo', $response->getHeaderLine('header1'));
    }
    
    public function testGetHtml()
    {
        $html = $this->factory->getHtml('view-with-context', ['world' => 'World']);
        $this->assertSame('Hello World', $html);
    }
    
    protected function packageProviders() :array
    {
        return [ViewServiceProvider::class];
    }
    
}