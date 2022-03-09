<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\ListenerFactory;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;
use Snicco\Component\EventDispatcher\ListenerFactory\PsrListenerFactory;
use stdClass;

/**
 * @internal
 */
final class PsrListenerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function test_create_with_entry_in_container(): void
    {
        $pimple = new Container();
        $std = new stdClass();
        $pimple[stdClass::class] = $std;

        $pimple_psr = new \Pimple\Psr11\Container($pimple);

        $factory = new PsrListenerFactory($pimple_psr);
        $this->assertSame($std, $factory->create(stdClass::class, 'foo_event'));
    }

    /**
     * @test
     */
    public function test_useless_exception_is_thrown_if_a_class_cant_be_created(): void
    {
        $pimple = new Container();

        $pimple_psr = new \Pimple\Psr11\Container($pimple);

        $factory = new PsrListenerFactory($pimple_psr);

        $this->expectException(CantCreateListener::class);
        $this->expectExceptionMessage('Cant create listener class [stdClass] for event [foo_event]');
        $factory->create(stdClass::class, 'foo_event');
    }
}
