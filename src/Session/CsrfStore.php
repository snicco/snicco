<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use WPEmerge\Support\Arr;

    class CsrfStore implements \ArrayAccess, \Countable, \Iterator
    {

        /**
         * @var SessionStore
         */
        private $session_store;

        private $csrf_iteration;


        public function __construct(SessionStore $session_store)
        {

            $this->session_store = $session_store;

            if ( ! $this->session_store->has('csrf')) {

                $this->session_store->put('csrf', []);

            }


        }

        private function csrf()
        {

            return $this->session_store->get('csrf', []);

        }

        public function offsetExists($offset) : bool
        {

            return $this->session_store->has('csrf.'.$offset);
        }

        public function offsetGet($offset)
        {

            return $this->session_store->get('csrf.'.$offset);
        }

        public function offsetSet($offset, $value)
        {

            $this->session_store->put('csrf.'.$offset, $value);
        }

        public function offsetUnset($offset)
        {

            $this->session_store->forget('csrf.'.$offset);
        }

        public function count() : int
        {

            $count = count($this->csrf());

            return $count;
        }

        public function current()
        {

            $this->checkIterator();

            return current($this->csrf_iteration);

        }

        public function next() :void
        {

            $this->checkIterator();

            next($this->csrf_iteration);


        }

        public function key()
        {

            $this->checkIterator();

            return key($this->csrf_iteration);

        }

        public function valid() : bool
        {

            $this->checkIterator();

            return key($this->csrf_iteration) !== null;

        }

        public function rewind() :void
        {

            $this->checkIterator();

            reset($this->csrf_iteration);
        }

        private function checkIterator()
        {

            if ( ! isset($this->csrf_iteration) || $this->csrf_iteration === null ) {

                $this->csrf_iteration = $this->csrf();

            }

            if ( count($this->csrf()) !== count($this->csrf_iteration) ) {

                $this->csrf_iteration = $this->csrf();

            }

        }

    }
