<?php

declare(strict_types=1);

namespace Tests\unit\View;

use LogicException;
use Snicco\Support\Str;
use Snicco\View\MethodField;
use PHPUnit\Framework\TestCase;

class MethodFieldTest extends TestCase
{
    
    /** @test */
    public function testCreateThrowsExceptionForBadMethods()
    {
        
        $this->expectException(LogicException::class);
        
        $field = $this->newMethodField();
        $html = $field->html('GET');
        
    }
    
    /** @test */
    public function testCreateHtml()
    {
        
        $field = $this->newMethodField();
        
        $html = $field->html('PUT');
        $this->assertStringStartsWith("<input type='hidden' name='_method' value=", $html);
        $this->assertStringContainsString('PUT|', $html);
        
    }
    
    /** @test */
    public function testValidate()
    {
        
        $field = $this->newMethodField();
        $html = $field->html('PUT');
        
        $value = Str::between($html, "value='", "'>");
        
        $this->assertSame('PUT', $field->validate($value));
        
    }
    
    /** @test */
    public function testValidateTampered()
    {
        
        $field = $this->newMethodField();
        $html = $field->html('PUT');
        
        $value = Str::between($html, "value='", "'>");
        
        $tampered = Str::replaceFirst('PUT', 'PATCH', $value);
        
        $this->assertSame(false, $field->validate($tampered));
        
    }
    
    /** @test */
    public function testValidateWithNotAllowedMethod()
    {
        
        $field = $this->newMethodField();
        $html = $field->html('PUT');
        
        $value = Str::between($html, "value='", "'>");
        
        $tampered = Str::replaceFirst('PUT', 'GET', $value);
        
        $this->assertSame(false, $field->validate($tampered));
        
    }
    
    private function newMethodField() :MethodField
    {
        return new MethodField(TEST_APP_KEY);
    }
    
}
