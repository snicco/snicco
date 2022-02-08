<?php

declare(strict_types=1);


namespace Snicco\Component\EventDispatcher\Tests\ListenerFactory;

use PHPUnit\Framework\TestCase;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;
use Snicco\Component\EventDispatcher\ListenerFactory\NewableListenerFactory;

final class NewableListenerFactoryTest extends TestCase
{

    /**
     * @test
     *
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress UndefinedClass
     */
    public function test_exception_for_bad_class(): void
    {
        $factory = new NewableListenerFactory();
        $this->expectException(CantCreateListener::class);
        $this->expectExceptionMessage(
            'The listener class [Bogus] could not be instantiated while dispatching [foo_event].'
        );
        $factory->create('Bogus', 'foo_event');
    }

}