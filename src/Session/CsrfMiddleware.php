<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Closure;
    use Exception;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Slim\Csrf\Guard;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Support\Arr;

    class CsrfMiddleware extends Guard
    {

        /**
         * @var SessionStore
         */
        private $session_store;

        public function __construct(ResponseFactory $response_factory, SessionStore $session_store)
        {

            $this->session_store = $session_store;
            $storage = $session_store->get('csrf', []);

            parent::__construct(
                $response_factory,
                'csrf',
                $storage,
                $this->throwExceptionOnFailure(),
                32

            );

        }

        /**
         * @param  Request  $request
         * @param  Delegate  $handler
         *
         * @return ResponseInterface
         * @throws Exception
         */
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

            $response = parent::process($request, $handler);

            $this->session_store->put('csrf', $this->storage);

            return $response;

        }

        public function generateToken() : array
        {
            if ( $this->session_store->get('csrf', []) !== [] ) {

                $csrf = $this->session_store->get('csrf');

                return [

                    $this->getTokenNameKey() => Arr::firstKey($csrf),
                    $this->getTokenValueKey() => Arr::firstEl($csrf),

                ];

            }

            return parent::generateToken();
        }

        private function throwExceptionOnFailure() : Closure
        {
            return function () {

                throw new InvalidCsrfTokenException(419, 'The link you followed expired.');

            };
        }

    }