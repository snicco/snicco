<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Fluent;
use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Bridge\Blade\DummyApplication;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;

final class BladeStandaloneTest extends BladeTestCase
{

    /**
     * @test
     */
    public function nested_views_can_be_rendered_relative_to_the_views_directory(): void
    {
        $view = $this->view_engine->make('nested.view');

        $this->assertViewContent('FOO', $view->toString());
    }

    /**
     * @test
     */
    public function a_dummy_application_is_put_into_the_container(): void
    {
        $this->assertInstanceOf(
            DummyApplication::class,
            Container::getInstance()->make(Application::class)
        );
    }

    /**
     * @test
     */
    public function test_exception_if_events_are_dispatched_with_incorrect_payload(): void
    {
        $c = Container::getInstance();
        /** @var Dispatcher $events */
        $events = $c['events'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected payload[0] to be instance of');

        $events->dispatch('composing: view', ['bogus']);
    }

    /**
     * @test
     */
    public function test_no_exception_if_config_is_already_instance_of_array_access(): void
    {
        Container::setInstance(null);

        $config = new Fluent();
        $config['foo'] = 'bar';
        Container::getInstance()['config'] = $config;

        $this->composers = new ViewComposerCollection(
            null,
            new GlobalViewContext()
        );
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->boostrap();

        $this->assertSame('bar', $config['foo']);
    }

}