<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Tests\Blade\BladeTestCase;
use Snicco\View\Exceptions\ViewRenderingException;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

class BladeFeaturesTest extends BladeTestCase
{
    
    use InteractsWithWordpressUsers;
    
    /** @test */
    public function xss_protection_works()
    {
        $view = $this->view('xss');
        
        $this->assertStringStartsWith('&lt;script', $view->toString());
    }
    
    /** @test */
    public function xss_encoding_can_be_disabled()
    {
        $view = $this->view('xss-disabled')
                     ->with('script', '<script type="text/javascript">alert("Hacked!");</script>');
        
        $this->assertStringStartsWith('<script', $view->toString());
    }
    
    /** @test */
    public function json_works()
    {
        $view = $this->view('json')->with('json', ['foo' => 'bar']);
        $content = $view->toString();
        $this->assertSame(['foo' => 'bar'], json_decode($content, true));
    }
    
    /** @test */
    public function if_works()
    {
        $view = $this->view('if')->with('records', ['foo']);
        $content = $view->toString();
        $this->assertViewContent('I have one record!', $content);
        
        $view = $this->view('if')->with('records', ['foo', 'bar']);
        $content = $view->toString();
        $this->assertViewContent('I have multiple records!', $content);
        
        $view = $this->view('if')->with('records', []);
        $content = $view->toString();
        $this->assertViewContent("I don't have any records!", $content);
    }
    
    /** @test */
    public function unless_works()
    {
        $view = $this->view('unless')->with('foo', 'foo');
        $content = $view->toString();
        $this->assertViewContent('', $content);
        
        $view = $this->view('unless')->with('foo', 'bar');
        $content = $view->toString();
        $this->assertViewContent('UNLESS', $content);
    }
    
    /** @test */
    public function empty_isset_works()
    {
        $view = $this->view('isset-empty')->with('isset', 'foo')->with('empty', 'blabla');
        $content = $view->toString();
        $this->assertViewContent('ISSET', $content);
        
        $view = $this->view('isset-empty')->with('empty', '');
        $content = $view->toString();
        $this->assertViewContent('EMPTY', $content);
    }
    
    /** @test */
    public function including_a_subview_works()
    {
        $view = $this->view('parent')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);
    }
    
    /** @test */
    public function include_if_works()
    {
        $view = $this->view('include-if')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);
        
        $view = $this->view('include-if-bogus')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function include_when_works()
    {
        $view = $this->view('include-when')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);
        
        $view = $this->view('include-when')->with(['greeting' => 'Hello', 'foo' => 'bogus']);
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }
    
    /** @test */
    public function include_unless_works()
    {
        $view = $this->view('include-unless')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
        $this->assertViewContent('', $content);
        
        $view = $this->view('include-unless')->with(['greeting' => 'Hello', 'foo' => 'bar']);
        $content = $view->toString();
        $this->assertViewContent('Hello Calvin', $content);
    }
    
    /** @test */
    public function include_first_works()
    {
        $view = $this->view('include-first')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
        $this->assertViewContent('Hello Calvin', $content);
    }
    
    /** @test */
    public function each_works()
    {
        $user1 = $this->createAdmin(['first_name' => 'Calvin']);
        $user2 = $this->createAdmin(['first_name' => 'John']);
        $user3 = $this->createAdmin(['first_name' => 'Jane']);
        
        $collection = collect([$user1, $user2, $user3]);
        
        $view = $this->view('each')->with(['users' => $collection]);
        $content = $view->toString();
        $this->assertViewContent('Calvin.John.Jane.', $content);
        
        $view = $this->view('each')->with(['users' => []]);
        $content = $view->toString();
        $this->assertViewContent('NO USERS', $content);
    }
    
    /** @test */
    public function raw_php_works()
    {
        $view = $this->view('raw-php');
        $content = $view->toString();
        $this->assertViewContent('10', $content);
    }
    
    /** @test */
    public function service_injection_is_forbidden()
    {
        $view = $this->view('service-injection');
        try {
            $view->toString();
            $this->fail("@service was allowed.");
        } catch (ViewRenderingException $e) {
            $this->assertStringStartsWith(
                "The service directive is not allowed. Dont use it. Its evil.",
                $e->getPrevious()->getMessage()
            );
        }
    }
    
    /** @test */
    public function section_directives_work()
    {
        $view = $this->view('section-child');
        $content = $view->toString();
        $this->assertViewContent('FOOBAZ', $content);
    }
    
    /** @test */
    public function php_files_can_be_rendered()
    {
        $view = $this->view('php-file');
        $content = $view->toString();
        $this->assertViewContent('PHPONLYFILE', $content);
    }
    
    private function view(string $view)
    {
        return $this->view_engine->make('blade-features.'.$view);
    }
    
}