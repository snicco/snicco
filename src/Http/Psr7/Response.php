<?php


    declare(strict_types = 1);


    namespace WPMvc\Http\Psr7;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;
    use WPMvc\Contracts\ResponsableInterface;
    use WPMvc\Http\Cookie;
    use WPMvc\Http\Cookies;


    class Response implements ResponseInterface, ResponsableInterface
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

        public function toResponsable()
        {
            return $this;
        }

        public function noIndex(?string $bot = null)
        {
            $value = $bot ? $bot .': noindex' : 'noindex';

            return $this->withAddedHeader('X-Robots-Tag', $value);
        }

        public function noFollow(?string $bot = null)
        {
            $value = $bot ? $bot .': nofollow' : 'nofollow';

            return $this->withAddedHeader('X-Robots-Tag', $value);
        }

        public function noRobots(?string $bot = null)
        {
            $value = $bot ? $bot .': none' : 'none';

            return $this->withAddedHeader('X-Robots-Tag', $value);
        }

        public function noArchive(?string $bot = null)
        {
            $value = $bot ? $bot .': noarchive' : 'noarchive';

            return $this->withAddedHeader('X-Robots-Tag', $value);
        }

        protected function new(ResponseInterface $new_psr_response) : self
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

        public function isRedirect(string $location = null): bool
        {
            return in_array($this->getStatusCode(), [201, 301, 302, 303, 307, 308]) && (null === $location || $location == $this->getHeader('Location'));
        }

        public function isSuccessful() : bool
        {
            return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
        }

        public function isOk() : bool
        {
            return 200 === $this->getStatusCode();
        }

        public function isNotFound() : bool
        {
            return 404 === $this->getStatusCode();
        }

        public function isForbidden() : bool
        {
            return 403 === $this->getStatusCode();
        }



    }