<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use LogicException;
use Snicco\Support\Str;
use Snicco\Http\MethodField;
use Tests\Codeception\shared\UnitTest;

use const TEST_APP_KEY;

class MethodFieldTest extends UnitTest
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
