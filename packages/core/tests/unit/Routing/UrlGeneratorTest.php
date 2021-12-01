<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Support\Str;
use Snicco\Support\Carbon;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\TestDoubles\TestRequest;

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
    
    /** @test */
    public function a_signed_url_can_be_created()
    {
        $url = $this->generator->signed('foo', 300, true);
        
        $parts = parse_url($url);
        $query = explode('&', $parts['query']);
        
        $this->assertSame('https', $parts['scheme']);
        $this->assertStringEndsWith($parts['host'], trim(SITE_URL, '/'));
        $this->assertSame('/foo', $parts['path']);
        $this->assertSame('expires='.Carbon::now()->addSeconds(300)->getTimestamp(), $query[0]);
        $this->assertStringContainsString('signature', $query[1]);
    }
    
    /** @test */
    public function a_relative_signed_url_can_be_created()
    {
        $url = $this->generator->signed('foo', 300, false);
        
        // Path
        $this->assertStringStartsWith('/foo', $url);
        
        // expires
        $this->assertStringContainsString(
            'expires='.Carbon::now()->addSeconds(300)->getTimestamp(),
            $url
        );
        
        // signature
        $this->assertStringContainsString('&signature=', $url);
    }
    
    /** @test */
    public function the_expiration_time_can_be_set()
    {
        $url = $this->generator->signed('foo', 500);
        
        $parts = parse_url($url);
        $query = explode('&', $parts['query']);
        
        $this->assertSame('/foo', $parts['path']);
        $this->assertSame('expires='.Carbon::now()->addSeconds(500)->getTimestamp(), $query[0]);
        $this->assertStringContainsString('signature', $query[1]);
    }
    
    
    /**
     * SIGNED URLS.
     */
    
    /** @test */
    public function urls_with_the_correct_signature_can_be_validated()
    {
        $url = $this->generator->signed('/foo');
        
        $this->assertTrue(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url))
        );
    }
    
    /** @test */
    public function a_relative_signed_url_can_be_validated()
    {
        $rel_url = $this->generator->signed('/foo', 300);
        
        // Full url check fails
        $request = $this->frontendRequest('GET', trim(SITE_URL, '/').'/'.trim($rel_url, '/'));
        $this->assertFalse($this->magic_link->hasValidSignature($request, true));
        
        // rel url check works
        $this->assertTrue($this->magic_link->hasValidRelativeSignature($request));
    }
    
    /** @test */
    public function any_modification_to_the_signed_url_will_invalidate_it()
    {
        $url = $this->generator->signed('/foo');
        
        $this->assertFalse(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url.'a'))
        );
        
        $this->assertTrue(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url))
        );
    }
    
    /** @test */
    public function an_equal_signature_is_invalid_if_its_expired()
    {
        $url = $this->generator->signed('/foo', 300);
        
        $this->assertTrue(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url))
        );
        
        Carbon::setTestNow(Carbon::now()->addSeconds(301));
        
        $this->assertFalse(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url))
        );
        
        Carbon::setTestNow();
    }
    
    /** @test */
    public function signed_urls_can_be_created_from_routes()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo/{bar}', function () {
                return 'foo';
            })->name('foo');
        });
        
        $url = $this->generator->signedRoute('foo', ['bar' => 'bar']);
        
        $this->assertStringContainsString(
            '?expires='.Carbon::now()->addSeconds(300)
                              ->getTimestamp(),
            $url
        );
        $this->assertStringContainsString('&signature=', $url);
        
        $this->assertFalse(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url.'a'))
        );
        
        $this->assertTrue(
            $this->magic_link->hasValidSignature($this->frontendRequest('GET', $url))
        );
    }
    
    /** @test */
    public function relative_signed_route_urls_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo/{bar}', function () {
                return 'foo';
            })->name('foo');
        });
        
        $rel_url = $this->generator->signedRoute('foo', ['bar' => 'bar']);
        
        $this->assertStringStartsWith('/foo/bar', $rel_url);
        $this->assertStringContainsString(
            '?expires='.Carbon::now()->addSeconds(300)
                              ->getTimestamp(),
            $rel_url
        );
        $this->assertStringContainsString('&signature=', $rel_url);
        
        $request = $this->frontendRequest('GET', SITE_URL.'/'.trim($rel_url, '/'));
        
        // Full url check fails
        $this->assertFalse($this->magic_link->hasValidSignature($request, true));
        
        // modified url fails
        $wrong_request = $this->frontendRequest('GET', SITE_URL.'/'.trim($rel_url.'a', '/'));
        
        $this->assertFalse($this->magic_link->hasValidRelativeSignature($wrong_request));
        $this->assertTrue($this->magic_link->hasValidRelativeSignature($request));
    }
    
    /** @test */
    public function signed_urls_can_be_created_with_additional_query_string()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo/{bar}', function () {
                return 'foo';
            })->name('foo');
        });
        
        $url =
            $this->generator->signedRoute('foo', ['bar' => 'bar', 'query' => ['name' => 'calvin']]);
        
        $this->assertStringContainsString(
            '?expires='.Carbon::now()->addSeconds(300)
                              ->getTimestamp(),
            $url
        );
        $this->assertStringContainsString('&signature=', $url);
        $this->assertStringContainsString('&name=calvin', $url);
        
        $this->assertTrue($this->magic_link->hasValidSignature(TestRequest::from('GET', $url)));
        
        $this->assertFalse(
            $this->magic_link->hasValidSignature(TestRequest::from('GET', $url.'a'))
        );
        
        $url_with_wrong_query_value = str_replace('name=calvin', 'name=john', $url);
        
        $this->assertFalse(
            $this->magic_link->hasValidSignature(
                TestRequest::from('GET', $url_with_wrong_query_value)
            )
        );
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
