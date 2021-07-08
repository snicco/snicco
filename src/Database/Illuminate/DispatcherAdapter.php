<?php


    declare(strict_types = 1);


    namespace BetterWP\Database\Illuminate;

    use BetterWP\Support\Str;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Illuminate\Contracts\Events\Dispatcher;

    class DispatcherAdapter implements Dispatcher
    {

        /**
         * @var WordpressDispatcher
         */
        private $dispatcher;

        public function __construct(WordpressDispatcher $dispatcher)
        {
            $this->dispatcher = $dispatcher;
        }

        public function listen($events, $listener = null)
        {
            /** @todo Explore compatibility for wildcard events. */
            if ( ! is_string($events) || Str::contains('*', $events) ) {
                throw new \RuntimeException('BetterWP does only support eloquent events registered as string at the moment.');
            }

            $this->dispatcher->listen($events, $listener);

        }

        public function hasListeners($eventName)
        {
            return $this->dispatcher->hasListeners($eventName);
        }

        public function subscribe($subscriber)
        {
            throw new \RuntimeException('BetterWP does not support event subscribing at the moment.');
        }

        public function until($event, $payload = [])
        {
            throw new \RuntimeException('BetterWP does not support event subscribing at the moment.');
        }

        public function dispatch($event, $payload = [], $halt = false)
        {
            return $this->dispatcher->dispatch($event, $payload);
        }

        public function push($event, $payload = [])
        {
            throw new \RuntimeException('BetterWP does not support event subscribing at the moment.');

        }

        public function flush($event)
        {
            throw new \RuntimeException('BetterWP does not support event subscribing at the moment.');
        }

        public function forget($event)
        {
            $this->dispatcher->forget($event);
        }

        public function forgetPushed()
        {
            throw new \RuntimeException('BetterWP does not support event subscribing at the moment.');
        }

    }