<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Exceptions;

    use Exception;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;
    use Snicco\Http\Responses\RedirectResponse;
    use Throwable;

    class FailedAuthenticationException extends Exception
    {

        private array   $old_input;
        private ?string $route = null;
        private Request $request;


        public function __construct($message, Request $request, ?array $old_input = null, $code = 0, Throwable $previous = null)
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