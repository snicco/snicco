<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Carbon\Carbon;
    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Traits\HasLottery;

    abstract class MagicLink
    {

        use HasLottery;
        use InteractsWithTime;

        protected $app_key;

        /** @var Request */
        protected $request;

        protected $lottery = [4, 100];

        public function setAppKey(string $app_key)
        {

            $this->app_key = $app_key;

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

        public function create(string $url, int $expires, Request $request) : string
        {

            $signature = $this->hash($url, $request);

            if ($this->hitsLottery($this->lottery)) {

                $this->gc();

            }

            $stored = $this->store($signature, $expires);

            if ( ! $stored ) {
                throw new \RuntimeException('Magic link could not be stored');
            }

            return $signature;

        }

        public function hasValidSignature(Request $request, $absolute = false) : bool
        {

            return $this->hasCorrectSignature($request, $absolute)
                && ! $this->signatureHasExpired($request)
                && $this->notUsed($request);

        }

        public function hasValidRelativeSignature(Request $request) : bool
        {

            return $this->hasValidSignature($request);

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

            $signature = $this->hash($url.'?'.$query_without_signature, $request);

            return hash_equals($signature, $request->query('signature', ''));

        }

        protected function hash(string $url, Request $request) : string
        {

            if ( ! $this->app_key) {
                throw new \RuntimeException('App key not set.');
            }


            $salt = $this->app_key. $request->userAgent();

            return hash_hmac('sha256', $url, $salt);

        }


    }