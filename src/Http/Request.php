<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Contracts\RouteCondition;
    use WPEmerge\ImplementsPsr7Request;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\UrlParser;
    use WPEmerge\Support\VariableBag;

    class Request implements ServerRequestInterface
    {
        use ImplementsPsr7Request;
        use InspectsRequest;



        public function __construct(ServerRequestInterface $psr_request)
        {

            $this->psr_request = $psr_request;

        }

        public function path() : string
        {
            return $this->getUri()->getPath();
        }

        public function url( bool $trailing_slash = false ) : string
        {
            $url = trim(preg_replace('/\?.*/', '', $this->getUri()), '/');

            if ( $trailing_slash ) {

                $url = $url . '/';

            }

            return $url;

        }

        public function fullUrl(bool $trailing_slash = false) : string
        {

            $full_url =  trim($this->getUri()->__toString());

            return  ($trailing_slash) ? $full_url . '/' : $full_url;

        }

        public function setRoute(RouteCondition $route)
        {

            $this->withAttribute('route', $route);


        }

        public function route() : ?RouteCondition
        {

            return $this->getAttribute('route', null );

        }

        public function setType ( string $type ) {

            $this->withAttribute('type', $type);

        }

        public function type() : string
        {

            return $this->getAttribute('type', '');

        }

        public function query(string $name, $default )
        {

            return Arr::get($this->getQueryParams(), $name, $default);

        }

        /** @todo verify this.  */
        public function request(string $name, $default )
        {

            return Arr::get($this->getParsedBody(), $name, $default);

        }




        private function isWpAdminPageRequest() : bool
        {

            return UrlParser::isWpAdminPageRequest( $this->getUri()->getPath() );

        }


    }