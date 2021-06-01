<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Exceptions\InvalidSignatureException;

    class ValidateSignature extends Middleware
    {

        /**
         * @var UrlGenerator
         */
        private $url_generator;
        /**
         * @var string
         */
        private $type;

        public function __construct(UrlGenerator $url_generator, string $type = 'absolute')
        {
            $this->url_generator = $url_generator;
            $this->type = $type;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( $this->url_generator->hasValidSignature($request, $this->type !== 'relative') ) {

                return $next($request);

            }

            throw new InvalidSignatureException();

        }

    }