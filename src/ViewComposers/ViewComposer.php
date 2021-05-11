<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ViewComposers;

	use Closure;
	use WPEmerge\Contracts\ViewComposer as ViewComposerInterface;
	use WPEmerge\Traits\ReflectsCallable;

	class ViewComposer implements ViewComposerInterface {

		use ReflectsCallable;

		/**
		 *
		 * A closures that wraps the actual view composer
		 * registered by the user.
		 *
		 * All view composers are resolved from the
		 * service container.
		 *
		 * @var \Closure
		 */
		private $executable_composer;

		public function __construct( Closure $executable_closure  ) {

			$this->executable_composer = $executable_closure;

		}


		public function executeUsing(...$args) {

			$closure = $this->executable_composer;

			$payload = $this->buildNamedParameters( $this->unwrap($closure), $args);

			return $closure($payload);


		}


	}