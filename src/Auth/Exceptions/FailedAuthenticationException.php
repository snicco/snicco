<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Exceptions;

    use Throwable;
    use WP_Error;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Http\Responses\RedirectResponse;

    class FailedAuthenticationException extends \Exception
    {

        /**
         * @var array
         */
        private $old_input;

        /**
         * @var string
         */
        private $route;

        /**
         * @var Request
         */
        private $request;


        public function __construct($message, Request $request, ?array $old_input = null , $code = 0, Throwable $previous = null)
        {
            $this->request = $request;
            $this->old_input = $old_input ?? $this->request->all();
            parent::__construct($message, $code, $previous);

        }

        public function redirectToRoute(string $route)
        {
            $this->route = $route;
        }

        public function render(ResponseFactory $response_factory) : RedirectResponse
        {

            $response = $response_factory->redirect();

            if ($this->route) {

                return $response->toRoute($this->route)
                                ->withErrors(['message' => $this->getMessage()])
                                ->withInput($this->old_input);

            }

            return $response_factory->redirect()
                                    ->refresh()
                                    ->withErrors(['message' => $this->getMessage()])
                                    ->withInput($this->old_input);
        }

    }