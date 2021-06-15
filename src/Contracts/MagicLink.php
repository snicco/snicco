<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Carbon\Carbon;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\HasLottery;

    abstract class MagicLink
    {

        use HasLottery;

        protected $app_key;

        /** @var Request */
        protected $request;

        protected $lottery = [2, 100];

        public function setAppKey(string $app_key)
        {

            $this->app_key = $app_key;

        }

        public function setRequest(Request $request)
        {

            $this->request = $request;
        }

        abstract public function notUsed(Request $request) : bool;

        abstract public function destroy($signature);

        abstract public function store(string $signature, int $expires) :bool;

        abstract public function gc() : bool;

        public function invalidate(string $url) {


            parse_str(parse_url($url)['query'] ?? '', $query);
            $signature = $query['signature'] ?? '';

            $this->destroy($signature);

        }

        public function create(string $url, int $expires) : string
        {

            $signature = wp_cache_get($url . $this->request->userAgent() ,'magic_links');

            if ( $signature !== false ) {
                return $signature;
            }

            $signature = $this->hash($url);

            if ($this->hitsLottery($this->lottery)) {

                $this->gc();

            }

            $stored = $this->store($signature, $expires);

            wp_cache_add($url . $this->request->userAgent(), $signature, 'magic_links', $expires);

            if ( ! $stored ) {
                throw new \RuntimeException('Magic link could not be stored');
            }

            return $signature;

        }

        public function hasValidSignature(Request $request, $absolute = true) : bool
        {

            return $this->hasCorrectSignature($request, $absolute)
                && ! $this->signatureHasExpired($request)
                && $this->notUsed($request);

        }

        public function hasValidRelativeSignature(Request $request) : bool
        {

            return $this->hasValidSignature($request, false);

        }

        private function signatureHasExpired(Request $request) : bool
        {

            $expires = $request->query('expires', null);

            if ( ! $expires) {
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

            $signature = $this->hash($url.'?'.$query_without_signature);

            return hash_equals($signature, $request->query('signature', ''));

        }

        protected function hash(string $url) : string
        {

            if ( ! $this->app_key) {
                throw new \RuntimeException('App key not set.');
            }

            if ( ! $this->request) {
                throw new \RuntimeException('Request not set.');
            }

            $salt = $this->app_key.$this->request->userAgent();

            return hash_hmac('sha256', $url, $salt);

        }


    }