<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;

class InspectsRequestTest extends TestCase
{

    use CreateTestPsr17Factories;
    use CreatesPsrRequests;
    use CreateUrlGenerator;

    public function testIsGet()
    {
        $request = $this->frontendRequest('/foo');
        $this->assertTrue($request->isGet());

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertFalse($request->isGet());
    }

    public function testIsPost()
    {
        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertTrue($request->isPost());

        $request = $this->frontendRequest('/', [], 'GET');
        $this->assertFalse($request->isPost());
    }

    public function testIsPut()
    {
        $request = $this->frontendRequest('/', [], 'PUT');
        $this->assertTrue($request->isPut());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isPut());
    }

    public function testIsPatch()
    {
        $request = $this->frontendRequest('/', [], 'PATCH');
        $this->assertTrue($request->isPatch());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isPatch());
    }

    public function testIsOptions()
    {
        $request = $this->frontendRequest('/', [], 'OPTIONS');
        $this->assertTrue($request->isOptions());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isOptions());
    }

    public function testIsDelete()
    {
        $request = $this->frontendRequest('/', [], 'DELETE');
        $this->assertTrue($request->isDelete());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isDelete());
    }

    public function testIsHead()
    {
        $request = $this->frontendRequest('/', [], 'HEAD');
        $this->assertTrue($request->isHead());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isHead());
    }

    public function testIsSafe()
    {
        $request = $this->frontendRequest('/', [], 'HEAD');
        $this->assertTrue($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'GET');
        $this->assertTrue($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'OPTIONS');
        $this->assertTrue($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'TRACE');
        $this->assertTrue($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'PUT');
        $this->assertFalse($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'DELETE');
        $this->assertFalse($request->isMethodSafe());

        $request = $this->frontendRequest('/', [], 'PATCH');
        $this->assertFalse($request->isMethodSafe());
    }

    public function testIsReadVerb()
    {
        $request = $this->frontendRequest('/', [], 'HEAD');
        $this->assertTrue($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'GET');
        $this->assertTrue($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'OPTIONS');
        $this->assertTrue($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'TRACE');
        $this->assertTrue($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'PUT');
        $this->assertFalse($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertFalse($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'DELETE');
        $this->assertFalse($request->isReadVerb());

        $request = $this->frontendRequest('/', [], 'PATCH');
        $this->assertFalse($request->isReadVerb());
    }

    public function testIsAjax()
    {
        $request = $this->frontendRequest('foo')
            ->withAddedHeader('X-Requested-With', 'XMLHttpRequest');

        $this->assertTrue($request->isAjax());
        $this->assertTrue($request->isXmlHttpRequest());

        $request = $this->frontendRequest('/foo');

        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isXmlHttpRequest());
    }

    public function testIsSendingJson()
    {
        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader('Content-Type', 'application/json');
        $this->assertTrue($request->isSendingJson());

        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader(
                'Content-Type',
                'application/x-www-form-urlencoded'
            );
        $this->assertFalse($request->isSendingJson());
    }

    public function testIsExpectingJson()
    {
        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader('Content-Type', 'application/json')
            ->withAddedHeader('Accept', 'application/json');

        $this->assertTrue($request->isExpectingJson());

        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader('Content-Type', 'application/json')
            ->withAddedHeader('Accept', 'text/html');

        $this->assertFalse($request->isExpectingJson());
    }

    public function testAccepts()
    {
        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader('Accept', 'application/json');

        $this->assertTrue($request->accepts('application/json'));
        $this->assertFalse($request->accepts('text/html'));

        $request = $this->frontendRequest('/', [], 'POST');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));

        $request = $this->frontendRequest('/', [], 'POST')->withAddedHeader('Accept', '*/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('text/html'));

        $request =
            $this->frontendRequest('/', [], 'POST')->withAddedHeader('Accept', 'application/*');
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/json+ld'));
        $this->assertFalse($request->accepts('text/html'));
    }

    public function testAcceptsOneOf()
    {
        $request = $this->frontendRequest('/', [], 'POST')
            ->withAddedHeader('Accept', 'application/json');

        $this->assertTrue($request->acceptsOneOf(['application/json', 'application/json+ld']));
        $this->assertFalse($request->acceptsOneOf(['text/html', 'application/json+ld']));
    }

    public function testAcceptsHtml()
    {
        $request = $this->frontendRequest('/', [], 'POST')->withAddedHeader('Accept', 'text/html');

        $this->assertTrue($request->acceptsHtml());

        $request = $request->withHeader('Accept', 'text/plain');
        $this->assertFalse($request->acceptsHtml());
    }

    public function testGetRealMethod()
    {
        $request = $this->frontendRequest('/foo', ['REQUEST_METHOD' => 'POST'], 'POST');

        $this->assertSame('POST', $request->realMethod());

        $request = $request->withMethod('PUT');

        $this->assertSame('POST', $request->realMethod());
    }

}