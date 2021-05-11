<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Session;

	use Closure;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;
	use stdClass;
	use WPEmerge\Contracts\Session;
	use SessionHandlerInterface;

	class SessionStore implements Session {

		/**
		 * @var string
		 */
		private $id;

		/**
		 * @var string
		 */
		private $name;

		/**
		 * @var array
		 */
		private $attributes = [];

		/**
		 * @var \SessionHandlerInterface
		 */
		private $handler;

		/**
		 * @var bool
		 */
		private $started = false;

		public function __construct(string $name, SessionHandlerInterface $handler, string $id = null)
		{
			$this->setId($id);
			$this->name = $name;
			$this->handler = $handler;
		}

		public function start() : bool {

			$this->loadSessionDataFromHandler();

			return $this->started = true;
		}

		public function save() :void
		{
			$this->ageFlashData();

			$this->handler->write( $this->getId(), serialize($this->attributes) );

			$this->started = false;
		}

		public function all() :array
		{
			return $this->attributes;
		}

		public function only( $keys) : array {

			return Arr::only($this->attributes, $keys);

		}

		public function exists($key) :bool
		{
			$placeholder = new stdClass;

			return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) use ($placeholder) {
				return $this->get($key, $placeholder) === $placeholder;
			});
		}

		public function missing($key) :bool
		{
			return ! $this->exists($key);
		}

		public function has($key) :bool
		{
			return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
				return is_null($this->get($key));
			});
		}

		public function get(string $key, $default = null)
		{
			return Arr::get($this->attributes, $key, $default);
		}

		public function pull(string $key, $default = null)
		{
			return Arr::pull($this->attributes, $key, $default);
		}

		public function hasOldInput(string $key = null) : bool {

			$old = $this->getOldInput($key);

			return is_null($key) ? count($old) > 0 : ! is_null($old);
		}

		public function getOldInput(string $key = null, $default = null)
		{
			return Arr::get($this->get('_old_input', []), $key, $default);
		}

		public function replace( array $attributes) :void
		{
			$this->put($attributes);
		}

		public function put($key, $value = null) :void
		{
			if ( ! is_array ($key ) ) {
				$key = [$key => $value];
			}

			foreach ($key as $arrayKey => $arrayValue) {
				Arr::set($this->attributes, $arrayKey, $arrayValue);
			}
		}

		public function remember(string $key, Closure $callback)
		{
			if (! is_null($value = $this->get($key))) {
				return $value;
			}

			return tap($callback(), function ($value) use ($key) {

				$this->put($key, $value);

			});
		}

		public function push(string $key, $value) :void
		{
			$array = $this->get($key, []);

			$array[] = $value;

			$this->put($key, $array);
		}

		public function increment(string $key, int $amount = 1) :int
		{
			$this->put($key, $value = $this->get($key, 0) + $amount);

			return $value;
		}

		public function decrement($key, $amount = 1) :int
		{
			return $this->increment($key, $amount * -1);
		}

		public function flash(string $key, $value = true) :void
		{
			$this->put($key, $value);

			$this->push('_flash.new', $key);

			$this->removeFromOldFlashData([$key]);
		}

		public function now(string $key, $value) :void
		{
			$this->put($key, $value);

			$this->push('_flash.old', $key);
		}

		public function reflash() :void
		{
			$this->mergeNewFlashes($this->get('_flash.old', []));

			$this->put('_flash.old', []);
		}

		public function keep($keys = null) :void
		{
			$this->mergeNewFlashes($keys = is_array($keys) ? $keys : func_get_args());

			$this->removeFromOldFlashData($keys);
		}

		public function flashInput(array $value) :void
		{
			$this->flash('_old_input', $value);
		}

		public function remove(string $key)
		{
			return Arr::pull($this->attributes, $key);
		}

		public function forget($keys) :void
		{
			Arr::forget($this->attributes, $keys);
		}

		public function flush() :void
		{
			$this->attributes = [];
		}

		public function invalidate() :bool
		{
			$this->flush();

			return $this->migrate(true);
		}

		public function regenerate( bool $destroy = false) :bool
		{
			return $this->migrate($destroy);
		}

		public function migrate( bool $destroy = false) :bool
		{
			if ($destroy) {
				$this->handler->destroy($this->getId());
			}

			$this->setId($this->generateSessionId());

			return true;
		}

		public function isStarted() :bool
		{
			return $this->started;
		}

		public function getId() :string
		{
			return $this->id;
		}

		public function getName() :string
		{
			return $this->name;
		}

		public function setName(string $name) :void
		{
			$this->name = $name;
		}

		public function setId(string $id) :void
		{
			$this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
		}

		public function isValidId(string $id) :bool
		{
			return ctype_alnum($id) && strlen($id) === 40;
		}


		public function previousUrl() :?string
		{
			return $this->get('_previous.url');
		}

		public function setPreviousUrl(string $url) :void
		{
			$this->put('_previous.url', $url);
		}

		public function getHandler() :SessionHandlerInterface
		{
			return $this->handler;
		}


		private function generateSessionId() :string
		{
			return Str::random(40);
		}

		private function ageFlashData() :void
		{
			$this->forget($this->get('_flash.old', []));

			$this->put('_flash.old', $this->get('_flash.new', []));

			$this->put('_flash.new', []);
		}

		private function loadSessionDataFromHandler() :void
		{
			$this->attributes = array_merge(
				$this->attributes,
				$this->readFromHandler()
			);
		}

		private function readFromHandler() : array {

			if ($data = $this->handler->read($this->getId())) {

				$data = @unserialize($data);

				if ($data !== false && ! is_null($data) && is_array($data)) {
					return $data;
				}
			}

			return [];
		}

		private function mergeNewFlashes(array $keys) :void
		{
			$values = array_unique(array_merge($this->get('_flash.new', []), $keys));

			$this->put('_flash.new', $values);
		}

		private function removeFromOldFlashData(array $keys) :void
		{
			$this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
		}

	}