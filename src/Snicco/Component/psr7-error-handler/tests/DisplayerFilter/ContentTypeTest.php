<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\DisplayerFilter;

use RuntimeException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextExceptionDisplayer2;

use function array_values;

final class ContentTypeTest extends TestCase
{
    
    /** @test */
    public function all_displayers_that_can_display_are_included()
    {
        $filter = new ContentType();
        $displayers = [
            $d1 = new PlaintTextExceptionDisplayer1(),
            $d2 = new PlainTextExceptionDisplayer2(),
            $d3 = new JsonExceptionDisplayer1(),
            $d4 = new JsonExceptionDisplayer2(),
        ];
        
        $e = new RuntimeException();
        $info = new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e);
        $request = new ServerRequest('GET', '/foo');
        
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain'),
            $info,
        );
        
        $this->assertSame([$d1, $d2], array_values($filtered));
        
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'application/json'),
            $info,
        );
        
        $this->assertSame([$d3, $d4], array_values($filtered));
    }
    
}

class PlaintTextExceptionDisplayer1 implements ExceptionDisplayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
        return '';
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}

class PlaintTextExceptionDisplayer2 implements ExceptionDisplayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
        return '';
    }
    
    public function supportedContentType() :string
    {
        return 'text/plain';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}

class JsonExceptionDisplayer1 implements ExceptionDisplayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
        return '';
    }
    
    public function supportedContentType() :string
    {
        return 'application/json';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}

class JsonExceptionDisplayer2 implements ExceptionDisplayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
        return '';
    }
    
    public function supportedContentType() :string
    {
        return 'application/json';
    }
    
    public function isVerbose() :bool
    {
        return false;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}