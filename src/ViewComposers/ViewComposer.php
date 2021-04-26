<?php


	namespace WPEmerge\ViewComposers;

	use Closure;
	use WPEmerge\Contracts\ViewComposer as ViewComposerInterface;

	class ViewComposer implements ViewComposerInterface {


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

		public function __construct(  Closure $executable_closure  ) {

			$this->executable_composer = $executable_closure;

		}

		public function executeUsing(...$args) {

			$closure = $this->executable_composer;

			$view = ['view' => $args[0]];

			return $closure($view);


		}


	}