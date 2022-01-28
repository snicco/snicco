<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Filter;

use RuntimeException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Displayer;
use Snicco\Component\Psr7ErrorHandler\Filter\ContentTypeFilter;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Tests\fixtures\PlainTextDisplayer2;

use function array_values;

final class ContentTypeFilterTest extends TestCase
{
    
    /** @test */
    public function all_displayers_that_can_display_are_included()
    {
        $filter = new ContentTypeFilter();
        $displayers = [
            $d1 = new PlaintTextDisplayer1(),
            $d2 = new PlainTextDisplayer2(),
            $d3 = new JsonDisplayer1(),
            $d4 = new JsonDisplayer2(),
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

class PlaintTextDisplayer1 implements Displayer
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

class PlaintTextDisplayer2 implements Displayer
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

class JsonDisplayer1 implements Displayer
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

class JsonDisplayer2 implements Displayer
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