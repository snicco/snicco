<?php

declare(strict_types=1);

namespace Tests\stubs;

use Snicco\Support\Str;
use PHPUnit\Framework\Assert;

use function trim;
use function explode;

class HeaderStack
{
    
    /**
     * @var string[][]
     */
    private static $data = [];
    
    /**
     * Reset state
     */
    public static function reset()
    {
        self::$data = [];
    }
    
    /**
     * Push a header on the stack
     *
     * @param  array  $header
     */
    public static function push(array $header)
    {
        self::$data[] = $header;
    }
    
    /**
     * Return the current header stack
     *
     * @return string[][]
     */
    public static function stack() :array
    {
        return self::$data;
    }
    
    public static function assertContains(string $header_name, $value)
    {
        
        self::assertHas($header_name);
        
        $header =
            collect(self::$data)->pluck('header')->first(function ($header) use ($header_name) {
                
                return Str::startsWith($header, $header_name);
                
            });
        
        Assert::assertStringContainsString(
            $value,
            $header,
            "the header {$header_name} does not contain {$value}"
        );
        
    }
    
    /**
     * Verify if there's a header line on the stack
     *
     * @param  string  $header
     * @param  string|null  $value
     */
    public static function assertHas(string $header, string $value = null)
    {
        
        $header_found = false;
        
        foreach (self::$data as $item) {
            
            $components = explode(':', $item['header']);
            
            if (trim(strtolower($components[0])) === strtolower($header)) {
                
                if ($value) {
                    
                    Assert::assertStringContainsString(
                        $value,
                        $actual = trim(Str::after($item['header'], ':')),
                        "The value for header [{$header}] is: [{$actual}]. Expected: [{$value}]"
                    );
                    
                }
                
                $header_found = true;
                break;
                
            }
            
        }
        
        Assert::assertTrue($header_found, "Header {$header} was expected but not found.");
        
    }
    
    public static function assertHasNone()
    {
        Assert::assertEmpty(self::$data, 'Headers were sent unexpectedly.');
    }
    
    public static function isEmpty() :bool
    {
        return self::$data === [];
    }
    
    public static function assertHasStatusCode(int $code)
    {
        if ( ! isset(self::$data[0]['status_code'])) {
            
            Assert::fail('Status code header not sent.');
            
        }
        
        Assert::assertSame(
            $actual = self::$data[0]['status_code'],
            $code,
            "Actual status code: {$actual}. Expected: {$code}"
        );
        
    }
    
    public static function assertNoStatusCodeSent()
    {
        Assert::assertSame(
            [],
            collect(self::$data)->pluck('status_code')->whereNotNull()->all(),
            'status code header was sent unexpectedly.'
        );
    }
    
}