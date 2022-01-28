<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Filter;

use RuntimeException;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Displayer;
use Snicco\Component\Psr7ErrorHandler\Filter\CanDisplayFilter;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_values;

final class CanDisplayFilterTest extends TestCase
{
    
    /** @test */
    public function all_displayers_that_can_display_are_included()
    {
        $filter = new CanDisplayFilter();
        $displayers = [
            $d1 = new CanDisplayRuntimeException(),
            $d2 = new CanDisplayRuntimeException2(),
            $d3 = new CanDisplayInvalidArgException(),
        ];
        
        $e = new RuntimeException();
        $request = new ServerRequest('GET', '/foo');
        
        $filtered = $filter->filter(
            $displayers,
            $request,
            new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e)
        );
        
        $this->assertSame([$d1, $d2], $filtered);
        
        $e = new InvalidArgumentException();
        
        $filtered = $filter->filter(
            $displayers,
            $request,
            new ExceptionInformation(500, 'foo_id', 'foo_title', 'foo_details', $e, $e)
        );
        
        $this->assertSame([$d3], array_values($filtered));
    }
    
}

class CanDisplayRuntimeException implements Displayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
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
        return $exception_information->originalException() instanceof RuntimeException;
    }
    
}

class CanDisplayRuntimeException2 implements Displayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
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
        return $exception_information->originalException() instanceof RuntimeException;
    }
    
}

class CanDisplayInvalidArgException implements Displayer
{
    
    public function display(ExceptionInformation $exception_information) :string
    {
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
        return $exception_information->originalException() instanceof InvalidArgumentException;
    }
    
}