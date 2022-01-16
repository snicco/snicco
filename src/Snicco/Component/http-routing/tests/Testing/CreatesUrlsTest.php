<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Testing;

use Generator;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Testing\CreatesUrls;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminArea;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;

final class CreatesUrlsTest extends TestCase
{
    
    use CreatesUrls;
    
    protected string    $host = 'foo.com';
    private WPAdminArea $admin_area;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->admin_area = WPAdminArea::fromDefaults();
        $this->generator = new InternalUrlGenerator(
            new RouteCollection([]),
            UrlGenerationContext::forConsole($this->host),
            $this->admin_area
        );
    }
    
    /**
     * @test
     * @dataProvider adminUrlInput
     */
    public function test_adminUrl($input)
    {
        $url = $this->adminUrl($input, ['bar' => 'baz']);
        
        $this->assertSame('https://foo.com/wp-admin/admin.php?bar=baz&page=foo', $url);
    }
    
    /**
     * @test
     * @dataProvider frontEndUrlInput
     */
    public function test_frontendUrl($input, $expected)
    {
        $url = $this->frontendUrl($input, ['biz' => 'boom']);
        
        $this->assertSame($expected, $url);
    }
    
    public function adminUrlInput() :Generator
    {
        yield ['admin.php/foo'];
        yield ['/admin.php/foo'];
        yield ['/admin.php/foo/'];
    }
    
    public function frontEndUrlInput() :Generator
    {
        yield ['/foo/bar/', 'https://foo.com/foo/bar/?biz=boom'];
        yield ['foo/bar/', 'https://foo.com/foo/bar/?biz=boom'];
        yield ['foo/bar', 'https://foo.com/foo/bar?biz=boom'];
        yield ['/foo/bar', 'https://foo.com/foo/bar?biz=boom'];
    }
    
    protected function adminArea() :AdminArea
    {
        return $this->admin_area;
    }
    
    protected function urlGenerator() :UrlGenerator
    {
        return $this->generator;
    }
    
}