<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http\Responses;


    use Illuminate\Contracts\Support\MessageProvider;
    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Session\Session;

    class RedirectResponse extends Response {

        /**
         * @var Session|null
         */
        private $session;

        public function to( string $url ) : RedirectResponse {

		    return $this->new($this->withHeader('Location', $url));

		}

        public function withSession(Session $session) : RedirectResponse
        {
            $this->session = $session;
            return $this;
        }

        /**
         * @param  string|array  $key
         * @param  mixed  $value
         * @return $this
         */
        public function with($key, $value = null) :RedirectResponse
        {
            $key = is_array($key) ? $key : [$key => $value];

            foreach ($key as $k => $v) {
                $this->session->flash($k, $v);
            }

            return $this;
        }

        public function withInput(array $input) : RedirectResponse
        {

            $this->checkSession();

            $this->session->flashInput($input);

            return $this;

        }

        /**
         * Flash a container of errors to the session.
         *
         * @param  MessageProvider|array  $provider
         * @param  string  $key
         * @return $this
         */
        public function withErrors($provider, string $key = 'default') : RedirectResponse
        {

            $this->checkSession();

            $value = $this->toMessageBag($provider);

            $errors = $this->session->errors();

            $this->session->flash(
                'errors', $errors->put($key, $value)
            );

            return $this;
        }


        private function toMessageBag($provider) : MessageBag
        {
            if ($provider instanceof MessageProvider) {
                return $provider->getMessageBag();
            }

            return new MessageBag((array) $provider);
        }


        private function checkSession () {


            if ( ! $this->session instanceof Session ) {

                $called_method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

                throw new \LogicException("The method: [RedirectResponse::{$called_method}] can only be used if session are enabled in the config.");
            }

        }

    }
