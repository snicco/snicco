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
		 * @var PhpViewEngine
		 */
		private $engine;

		/**
		 * @var string
		 */
		private $filepath;

		/**
		 * @var \WPEmerge\View\PhpView|null
		 */
		private $parent_view;

		/**
		 * @var array
		 */
		private $context = [];

		/**
		 * @var string
		 */
		private $name;

		public function __construct( PhpViewEngine $engine, string $name , string $path ) {

			$this->engine = $engine;
			$this->name = $name;
			$this->filepath = $path;


		}

		public function getFilepath() : string {

			return $this->filepath;
		}

		public function parent() : ?PhpView {

			return $this->parent_view;
		}

		public function withParentView( ?ViewInterface $layout ) : PhpView {

			$this->parent_view = $layout;

			return $this;

		}

		public function toString() : string {

			return $this->engine->renderPhpView($this);

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


	}
