<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\AbstractController;

class ControllerTest extends TestCase
{

    /**
     * @test
     */
    public function middleware_can_be_added_to_are_controller(): void
    {
        $subject = new TestAbstractController();

        $this->assertSame(['foo', 'baz'], $subject->getMiddleware());
    }

    /**
     * @test
     */
    public function middleware_can_be_added_for_some_methods_only(): void
    {
        $subject = new TestAbstractController();

        $this->assertSame(['foo', 'bar', 'baz'], $subject->getMiddleware('foo_method'));
    }

    /**
     * @test
     */
    public function middleware_can_be_added_for_all_methods_expect_some(): void
    {
        $subject = new TestAbstractController();

        $this->assertSame(['foo', 'bar', 'baz'], $subject->getMiddleware('foo_method'));

        $this->assertSame(['foo', 'baz'], $subject->getMiddleware('foo'));

        $this->assertSame(['foo'], $subject->getMiddleware('bar_method'));
    }

    /**
     * @test
     */
    public function blacklist_and_whitelist_cant_be_combined(): void
    {
        $this->expectExceptionMessage(
            'The only() method cant be combined with the except() method for one middleware'
        );

        $subject = new InvalidAbstractController();
    }

}

class TestAbstractController extends AbstractController
{

    public function __construct()
    {
        $this->middleware('foo');

        $this->middleware('bar')->only('foo_method');

        $this->middleware('baz')->except(['bar_method', 'biz_method']);
    }

}

class InvalidAbstractController extends AbstractController
{

    public function __construct()
    {
        $this->middleware('bar')->only('foo_method')->except('bar');
    }

}
