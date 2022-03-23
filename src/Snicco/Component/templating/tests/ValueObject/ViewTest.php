<?php

declare(strict_types=1);


namespace Snicco\Component\Templating\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\ValueObject\FilePath;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;

use function dirname;

final class ViewTest extends TestCase
{

    /**
     * @test
     */
    public function that_a_valid_view_can_be_constructed(): void
    {
        $view = new View(
            'foo',
            FilePath::fromString(dirname(__DIR__) . '/fixtures/views/foo.php'),
            PHPViewFactory::class,
            ['foo' => 'bar']
        );

        $this->assertSame('foo', $view->name());
        $this->assertSame(dirname(__DIR__) . '/fixtures/views/foo.php', (string)$view->path());
        $this->assertSame(['foo' => 'bar'], $view->context());
        $this->assertSame(PHPViewFactory::class, $view->viewFactoryClass());
    }

    /**
     * @test
     */
    public function that_a_view_is_immutable(): void
    {
        $view = new View(
            'foo',
            FilePath::fromString(dirname(__DIR__) . '/fixtures/views/foo.php'),
            PHPViewFactory::class
        );
        $this->assertSame([], $view->context());

        $view1 = $view->with('foo', 'bar');
        $this->assertSame([
            'foo' => 'bar',
        ], $view1->context());

        $this->assertSame([], $view->context());

        $view = $view->with('foo', 'baz');
        $this->assertSame([
            'foo' => 'baz',
        ], $view->context());

        $view = $view->with('bar', 'biz');
        $this->assertSame([
            'foo' => 'baz',
            'bar' => 'biz',
        ], $view->context());
    }

}