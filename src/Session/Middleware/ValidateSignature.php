<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Exceptions\InvalidSignatureException;

    class ValidateSignature extends Middleware
    {

        /**
         * @var string
         */
        private $type;

        /**
         * @var MagicLink
         */
        private $magic_link;

        public function __construct(MagicLink $magic_link, string $type = 'absolute')
        {
            $this->type = $type;
            $this->magic_link = $magic_link;
        }

        public function handle(Request $request, Delegate $next)
        {

            $valid = $this->magic_link->hasValidSignature($request, $this->type !== 'relative');

            if ( $valid ) {

                $this->magic_link->invalidate($request->fullUrl());

                return $next($request);


            }

            throw new InvalidSignatureException();

        }

    }