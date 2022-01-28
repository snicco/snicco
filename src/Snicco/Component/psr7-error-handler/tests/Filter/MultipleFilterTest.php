<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Filter;

use RuntimeException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Displayer;
use Snicco\Component\Psr7ErrorHandler\Filter\MultipleFilter;
use Snicco\Component\Psr7ErrorHandler\Filter\VerbosityFilter;
use Snicco\Component\Psr7ErrorHandler\Filter\ContentTypeFilter;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_values;

final class MultipleFilterTest extends TestCase
{
    
    /** @test */
    public function all_displayers_that_should_display_are_included()
    {
        $filter = new MultipleFilter(
            new VerbosityFilter(true),
            new ContentTypeFilter()
        );
        
        $displayers = [
            $d1 = new VerbosePlain(),
            $d2 = new NonVerbosePlain(),
            $d3 = new VerboseJson(),
            $d4 = new NonVerboseJson(),
        ];
        
        $e = new RuntimeException();
        $info = new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e);
        $request = new ServerRequest('GET', '/foo');
        
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain'),
            $info
        );
        
        $this->assertSame([$d1, $d2], array_values($filtered));
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'application/json'),
            $info
        );
        
        $this->assertSame([$d3, $d4], array_values($filtered));
        
        $filter = new MultipleFilter(
            new VerbosityFilter(false),
            new ContentTypeFilter()
        );
        
        $filtered = $filter->filter(
            $displayers,
            $request->withHeader('Accept', 'text/plain'),
            $info
        );
        
        $this->assertSame([$d2], array_values($filtered));
    }
    
}

class VerbosePlain implements Displayer
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
        return true;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}

class NonVerbosePlain implements Displayer
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

class VerboseJson implements Displayer
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
        return true;
    }
    
    public function canDisplay(ExceptionInformation $exception_information) :bool
    {
        return true;
    }
    
}

class NonVerboseJson implements Displayer
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