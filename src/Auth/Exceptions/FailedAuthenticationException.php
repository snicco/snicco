<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Exceptions;

    use Throwable;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;

    class FailedAuthenticationException extends \Exception
    {

        /**
         * @var array
         */
        private $old_input;

        public function __construct($message, array $old_input, $code = 0, Throwable $previous = null)
        {

            parent::__construct($message, $code, $previous);
            $this->old_input = $old_input;

        }

        public function oldInput() : array
        {

            return $this->old_input;
        }

        public function render(ResponseFactory $response_factory) : RedirectResponse
        {

            return $response_factory->redirect()
                                      ->refresh()
                                      ->withErrors(['message' => $this->getMessage()])
                                      ->withInput($this->oldInput());
        }

    }