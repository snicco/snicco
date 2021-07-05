<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use BetterWP\Contracts\MagicLink;
    use BetterWP\Http\Psr7\Request;

    class TestMagicLink extends MagicLink
    {

        private $links = [];

        public function __construct()
        {
            $this->app_key = TEST_APP_KEY;
        }

        public function notUsed(Request $request) : bool
        {
           return isset($this->links[$request->query('signature')]);
        }

        public function destroy($signature)
        {
            unset($this->links[$signature]);
        }

        public function store(string $signature, int $expires) : bool
        {
            $this->links[$signature] = $expires;
            return true;
        }

        public function gc() : bool
        {

            foreach ($this->links as $signature => $expires) {

                if( $expires < $this->currentTime() ) {
                    unset($this->links[$signature]);
                }

           }

            return true;
        }

        public function getStored() : array
        {
            return $this->links;
        }
    }