<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\EventDispatcher\Dispatcher\FakeDispatcher;

trait InteractsWithEvents
{
    
    /** @var FakeDispatcher */
    protected $dispatcher;
    
    /**
     * @param  string|array  $events
     */
    protected function fakeEvents($events) :FakeDispatcher
    {
        $this->dispatcher->fake($events);
        return $this->dispatcher;
    }
    
    /**
     * @param  string|array  $events
     */
    protected function fakeExcepts($events)
    {
        $this->dispatcher->fakeExcept($events);
        return $this->dispatcher;
    }
    
    protected function fakeAll() :FakeDispatcher
    {
        $this->dispatcher->fakeAll();
        return $this->dispatcher;
    }
    
    protected function resetFakedEvents()
    {
        $this->dispatcher->reset();
    }
    
    //protected function mailFake() :self
    //{
    //    $this->fakeEvents(PendingMail::class);
    //    return $this;
    //}
    //
    //protected function clearSentMails() :self
    //{
    //    $this->resetFakedEvents();
    //    return $this;
    //}
    //
    //protected function assertMailSent(string $mailable) :AssertableMail
    //{
    //
    //    $this->dispatcher->assertDispatched(
    //        PendingMail::class,
    //        function (PendingMail $event) use ($mailable) {
    //            return $event->mail instanceof $mailable;
    //        },
    //        "The mail [$mailable] was not sent."
    //    );
    //
    //    $events = $fake_dispatcher->allOfType(PendingMail::class);
    //
    //    PHPUnit::assertSame(
    //        1,
    //        $actual = count($events),
    //        "The mail [$mailable] was sent [$actual] times."
    //    );
    //
    //    return new AssertableMail($events[0], $this->app->resolve(ViewFactoryInterface::class));
    //}
    //
    //protected function assertMailNotSent(string $mailable)
    //{
    //    $fake_dispatcher = Event::dispatcher();
    //
    //    $this->checkMailWasFaked($fake_dispatcher);
    //
    //    $fake_dispatcher->assertNotDispatched(
    //        PendingMail::class,
    //        function (PendingMail $event) use ($mailable) {
    //            return $event->mail instanceof $mailable;
    //        },
    //        "The mail [$mailable] was not supposed to be sent."
    //    );
    //}
    
}