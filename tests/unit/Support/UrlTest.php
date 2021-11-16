<?php

declare(strict_types=1);

namespace Tests\unit\Support;

use Tests\UnitTest;
use Snicco\Support\Url;

class UrlTest extends UnitTest
{
    
    /** @test */
    public function rebuildQueryValidQuery()
    {
        $search = rawurlencode('foo bar');
        $result = Url::rebuild("https://foobar.com/foo/bar?search=$search");
        $this->assertSame("https://foobar.com/foo/bar?search=foo%20bar", $result);
    }
    
    /** @test */
    public function rebuildQueryInvalidQuery()
    {
        $search = 'foo bar';
        $result = Url::rebuild("https://foobar.com/foo/bar?search=$search");
        $this->assertSame("https://foobar.com/foo/bar?search=foo%20bar", $result);
    }
    
}