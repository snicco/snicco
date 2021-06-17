<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Carbon\Carbon;
    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
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

        public function hasAccessToRoute(Request $request) : bool
        {

            if ( $request->hasSession() ) {

                return $request->session()->canAccessRoute($request->fullPath());

            }

            $cookie = $request->cookies()->get($this->accessCookieName($request), '');

            return $cookie === $this->hash($request->fullPath(), $request);

        }

        public function withPersistentAccessToRoute(Response $response, Request $request) : Response
        {

            if ($request->hasSession()) {

                $request->session()
                        ->allowAccessToRoute($request->fullPath(), $request->query('expires'));

            }
            else {

                $response = $this->addAccessCookie($response, $request);

            }

            return $response;

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

        private function accessCookieName(Request $request) {


            $id = WP::isUserLoggedIn() ? WP::userId() : '';
            $path = $request->fullPath();
            $agent = $request->userAgent();

            return hash_hmac('sha256', $id.$path.$agent , $this->app_key);

        }

        private function addAccessCookie(Response $response, Request $request) : Response
        {

            $value = $this->hash($request->fullPath(), $request);

            $cookie = new Cookie($this->accessCookieName($request), $value);
            $cookie->expires( $request->expires() )
                   ->path($request->path())
                   ->onlyHttp();

            return $response->withCookie($cookie);

        }


    }