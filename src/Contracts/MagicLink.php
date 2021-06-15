<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Carbon\Carbon;
    use WPEmerge\Http\Psr7\Request;

    abstract class MagicLink
    {

        protected $app_key;

        /** @var Request */
        protected $request;

        public function setAppKey(string $app_key) {

            $this->app_key = $app_key;

        }

        public function setRequest( Request $request ) {
            $this->request = $request;
        }

        abstract public function notUsed(string $url) : bool;

        abstract public function invalidate(string $url );

        abstract public function create( string $url, int $expires ) : string;

        abstract public function gc () :bool;

        public function hasValidSignature(Request $request, $absolute = true ) : bool
        {

            return $this->hasCorrectSignature($request, $absolute)
                && ! $this->signatureHasExpired($request)
                && $this->notUsed($request->fullUrl());

        }

        public function hasValidRelativeSignature(Request $request ) : bool
        {

            return $this->hasValidSignature($request, false);

        }

        private function signatureHasExpired(Request $request) : bool
        {
            $expires = $request->query('expires', null );

            if ( ! $expires ) {
                return false;
            }

            return Carbon::now()->getTimestamp() > $expires;

        }

        private function hasCorrectSignature(Request $request, $absolute = true) : bool
        {

            $url = $absolute ? $request->url() : $request->path();

            $query_without_signature = preg_replace(
                '/(^|&)signature=[^&]+/',
                '',
                $request->queryString());

            $query_without_signature = ltrim($query_without_signature, '&');

            $signature = $this->hash( $url.'?'.$query_without_signature);

            return hash_equals($signature, $request->query('signature', ''));

        }

        protected function hash ( string $url ) :string {

            if ( ! $this->app_key ) {
                throw new \RuntimeException('App key not set.');
            }

             if ( ! $this->request ) {
                throw new \RuntimeException('Request not set.');
            }



            $salt = $this->app_key . $this->request->userAgent();

            return hash_hmac('sha256', $url, $salt);

        }

    }