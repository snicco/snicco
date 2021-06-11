<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http\Psr7;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\ImplementsPsr7Response;

    class Response implements ResponseInterface
    {

        use ImplementsPsr7Response;

        /**
         * @var Cookies
         */
        protected $cookies;

        public function __construct(ResponseInterface $psr7_response)
        {

            $this->psr7_response = $psr7_response;

        }



        protected function new(ResponseInterface $new_psr_response) : Response
        {

            $new = clone $this;
            $new->setPsr7Response($new_psr_response);
            return $new;

        }

        protected function setPsr7Response(ResponseInterface $psr_response)
        {

            $this->psr7_response = $psr_response;

        }

        public function withCookie(Cookie $cookie) : Response
        {

            if ( ! $this->cookies instanceof Cookies) {
                $this->cookies = new Cookies();
            }

            $this->cookies->set($cookie->name(), $cookie->properties());

            return clone $this;

        }

        public function withoutCookie(string $name, string $path = '/') : Response
        {
            if ( ! $this->cookies instanceof Cookies) {
                $this->cookies = new Cookies();
            }

            $cookie = new Cookie($name, 'deleted');
            $cookie->expires(1)->path($path);

            $this->cookies->add($cookie);

            return clone $this;
        }

        public function cookies() : Cookies
        {

            if ( ! $this->cookies instanceof Cookies) {

                return new Cookies();

            }

            return $this->cookies;

        }

        public function html(StreamInterface $html) : Response
        {

            return $this->withHeader('Content-Type', 'text/html')
                        ->withBody($html);

        }

        public function json(StreamInterface $json) : Response
        {

            return $this->withHeader('Content-Type', 'application/json')
                        ->withBody($json);

        }


    }