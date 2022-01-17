<?php

declare(strict_types=1);

namespace Tests\ViewBundle\integration;

use Snicco\View\ViewEngine;
use Snicco\ViewBundle\ViewServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;

final class MethodOverwriteTest extends FrameworkTestCase
{
    
    /** @test */
    public function a_method_field_can_be_rendered()
    {
        $this->bootApp();
        /** @var ViewEngine $view_engine */
        $view_engine = $this->app->resolve(ViewEngine::class);
        
        $content = $view_engine->render('method-field');
        $this->assertViewContent("<input type='hidden' name='_method' value='PUT'>", $content);
    }
    
    protected function packageProviders() :array
    {
        return [ViewServiceProvider::class];
    }
    
}