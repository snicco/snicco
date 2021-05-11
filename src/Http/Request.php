<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteCondition;

	class Request extends SymfonyRequest implements RequestInterface {

		public static function capture() : RequestInterface {

			static::enableHttpMethodParameterOverride();

			return static::createFromBase(SymfonyRequest::createFromGlobals());

		}

		private static function createFromBase( SymfonyRequest $request) : Request {

			$newRequest = (new static)->duplicate(
				$request->query->all(), $request->request->all(), $request->attributes->all(),
				$request->cookies->all(), $request->files->all(), $request->server->all()
			);

			$newRequest->headers->replace($request->headers->all());

			$newRequest->content = $request->content;

			return $newRequest;
		}

		public function method() : string {

			return $this->getMethod();

		}

		public function path() : string {

			$pattern = trim($this->getPathInfo(), '/');

			return $pattern === '' ? '/' : $pattern;

		}

		public function url() :string
		{
			return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
		}

		public function fullUrl() : string
		{
			$query = $this->getQueryString();

			$question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

			return $query ? $this->url().$question.$query : $this->url();

		}

		public function isGet() :bool {

			return $this->isMethod('GET');

		}

		public function isHead() :bool {

			return $this->isMethod('HEAD');

		}

		public function isPost() :bool {

			return $this->isMethod('POST');

		}

		public function isPut() :bool {

			return $this->isMethod('PUT');

		}

		public function isPatch() :bool {

			return $this->isMethod('PATCH');

		}

		public function isDelete() :bool {

			return $this->isMethod('DELETE');

		}

		public function isOptions() :bool {

			return $this->isMethod('OPTIONS');

		}

		public function isReadVerb() :bool {

			return $this->isMethodSafe();

		}

		public function isAjax() :bool {

			return $this->isXmlHttpRequest();

		}

		public function attribute( string $key = '', $default = null ) {

			return $this->attributes->get($key, $default);

		}

		public function query( $key = '', $default = null ) {

			return $this->query->get($key, $default);

		}

		public function body( $key = '', $default = null ) {

			return $this->request->get($key, $default);

		}

		public function cookies( $key = '', $default = null ) {

			return $this->cookies->get($key, $default);

		}

		public function files( $key = '', $default = null ) {

			return $this->files->get( $key , $default );

		}

		public function server( $key = '', $default = null ) {

			return $this->files->get($key, $default);

		}

		public function headers( $key = '', $default = null ) {

			return $this->headers->get($key, $default);

		}

		public function setRoute( RouteCondition $route ) {

			$this->attributes->set('route', $route);

		}

		public function route() : ?RouteCondition {

			return $this->attributes->get('route', null);

		}

		public function isPJAX() :bool
		{
			return $this->headers->get('X-PJAX') == true;
		}

		public function expectsJson() : bool {

			return $this->isAjax() && ! $this->isPJAX();

		}

		public function setType( string $request_event ) : void {

			$this->attributes->set('type', $request_event);

		}

		public function type() : string {

			return $this->attributes->get('type');

		}

		public function scheme() : string {

			return $this->getScheme();

		}

	}