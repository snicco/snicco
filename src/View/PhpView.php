<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Exceptions\ViewException;
	use WPEmerge\Http\Response;
	use WPEmerge\Support\Arr;


	class PhpView implements ViewInterface {

		/**
		 * PHP view engine.
		 *
		 * @var PhpViewEngine
		 */
		private $engine;

		/**
		 * Filepath to view.
		 *
		 * @var string
		 */
		private $filepath = '';

		/**
		 * @var ?\WPEmerge\View\PhpView
		 */
		private $layout;

		/**
		 * @var array
		 */
		private $context = [];

		/** @var string */
		private $name = '';

		public function __construct( PhpViewEngine $engine ) {

			$this->engine = $engine;

		}

		public function getFilepath() : string {

			return $this->filepath;
		}

		public function setFilepath( string $filepath ) : PhpView {

			$this->filepath = $filepath;

			return $this;
		}

		public function getLayout() : ?PhpView {

			return $this->layout;
		}

		public function setLayout( ?ViewInterface $layout ) : PhpView {

			$this->layout = $layout;

			return $this;
		}

		public function toString() : string {

			$ob_level = ob_get_level();

			ob_start();

			try {

				$this->requireView();

			}
			catch ( \Throwable $e ) {

				$this->handleViewException($e, $ob_level);

			}

			return ob_get_clean();

		}

		private function requireView () {

			$this->engine->pushLayoutContent( $this );

			if ( $this->getLayout() !== null ) {
				return $this->getLayout()->requireView();
			}

			$this->engine->includeChildViews();

		}

		private function handleViewException(\Throwable $e , $ob_level) {

			while (ob_get_level() > $ob_level) {
				ob_end_clean();
			}

			throw new ViewException('Error rendering view: [' . $this->getName() . '].');

		}

		public function toResponse() : ResponseInterface {

			return ( new Response( $this->toString() ) )->setType( 'text/html' );

		}

		/**
		 *
		 * @param  string|null  $key
		 * @param  mixed|null  $default
		 *
		 * @return mixed
		 */
		public function getContext( string $key = null, $default = null ) {

			if ( $key === null ) {
				return $this->context;
			}

			return Arr::get( $this->context, $key, $default );
		}

		/**
		 *
		 * @param  string|array<string, mixed>  $key
		 * @param  mixed  $value
		 *
		 * @return static                      $this
		 */
		public function with( $key, $value = null ) : ViewInterface {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->getContext(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

			return $this;
		}

		public function getName() : string {

			return $this->name;
		}

		public function setName( string $name ) : PhpView {

			$this->name = $name;

			return $this;
		}

	}
