<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Support\Str;
use Tests\Core\RoutingTestCase;

class UrlGeneratorTest extends RoutingTestCase
{
    
    /** @test */
    public function an_absolute_url_can_be_created_from_a_path()
    {
        $url = $this->generator->to('foo', [], true, true);
        
        $this->seeUrl('/foo', $url);
    }
    
    /** @test */
    public function a_relative_url_can_be_created_from_a_path()
    {
        $url = $this->generator->to('foo', [], true, false);
        
        $this->assertSame('/foo', $url);
    }
    
    /** @test */
    public function the_url_scheme_can_be_set()
    {
        $url = $this->generator->to('foo', [], true, true);
        
        $this->seeUrl('/foo', $url, true);
        
        $url = $this->generator->to('foo', [], false, true);
        $this->seeUrl('/foo', $url, false);
    }
    
    /** @test */
    public function query_fragments_can_be_added_to_the_url()
    {
        $url = $this->generator->to('base', [
            'foo' => 'bar',
            'baz' => 'biz',
            'boo',
        ], true, true);
        
        // boo not present due to the numerical index
        $this->seeUrl('/base?foo=bar&baz=biz', $url);
    }
    
    /** @test */
    public function fragments_can_be_included()
    {
        $url = $this->generator->to('base#section');
        $this->assertSame('/base#section', $url);
        
        $url = $this->generator->to('base#section', ['foo' => 'bar', 'baz' => 'biz']);
        $this->assertSame('/base?foo=bar&baz=biz#section', $url);
    }
    
    /** @test */
    public function secure_urls_can_be_create_as_an_alias()
    {
        $url = $this->generator->secure('foo', []);
        
        $this->seeUrl('/foo', $url);
    }
    
    /** @test */
    public function query_string_arguments_can_be_added_to_route_urls()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo/{bar}', function () {
                return 'foo';
            })->name('foo');
        });
        
        $url = $this->generator->toRoute('foo', ['bar' => 'bar', 'query' => ['name' => 'calvin']]);
        
        $this->assertSame('/foo/bar?name=calvin', $url);
    }
    
    /** @test */
    public function if_trailing_slashes_are_used_generated_urls_end_with_a_trailing_trash()
    {
        $url = $this->newUrlGenerator(null, true)->to('foo', [], true, true);
        
        $this->seeUrl('/foo/', $url);
    }
    
    /** @test */
    public function trailing_slashes_are_not_added_if_the_url_ends_with_dot_php()
    {
        $g = $this->newUrlGenerator(null, true);
        
        $path = '/wp-admin/index.php';
        $url = $g->to($path);
        $this->assertSame('/wp-admin/index.php', $url);
        
        $url = 'https://foo.com/wp-admin/index.php';
        $url = $g->to($url);
        $this->assertSame('/wp-admin/index.php', $url);
    }
    
    /** @test */
    public function trailing_slashes_are_not_appended_to_the_query_string_for_absolute_urls()
    {
        $g = $this->newUrlGenerator(null, true);
        
        $path = 'https://foo.com/foo/?page=bar';
        $url = $g->to($path, [], true, true);
        $this->assertSame('https://foo.com/foo/?page=bar', $url);
        
        $path = 'https://foo.com/foo?page=bar';
        $url = $g->to($path, [], true, true);
        $this->assertSame('https://foo.com/foo/?page=bar', $url);
    }
    
    private function seeUrl($route_path, $url, bool $secure = true)
    {
        $expected = rtrim(SITE_URL, '/').'/'.ltrim($route_path, '/');
        
        // Strip https/http since we don't know the scheme of SITE_URL is.
        $expected = Str::after($expected, '://');
        $result = Str::after($url, '://');
        
        $this->assertSame($expected, $result);
        
        $scheme = $secure ? 'https://' : 'http://';
        
        $this->assertStringStartsWith($scheme, $url);
    }
    
}
