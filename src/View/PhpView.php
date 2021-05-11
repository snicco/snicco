<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\PhpViewInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Http\Response;
	use WPEmerge\Support\Arr;


	class PhpView implements PhpViewInterface {

		/**
		 * Name of view file header based on which to resolve parent views.
		 *
		 * @var string
		 */
		public const PARENT_FILE_INDICATOR = 'Layout';

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
			$this->parent_view = $this->parseParentView();

		}

		public function path() : string {

			return $this->filepath;
		}

		public function parent() : ?PhpViewInterface {

			return $this->parent_view;
		}

		public function name() : string {

			return $this->name;
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
		public function context( string $key = null, $default = null ) {

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
				$this->context = array_merge( $this->context(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

			return $this;
		}

		/**
		 * Create a view instance for the given view's layout header, if any.
		 *
		 * @return ViewInterface|\WPEmerge\View\PhpView|null
		 */
		private function parseParentView() : ?PhpView {

			if ( empty( $file_headers = $this->parseFileHeaders() ) ) {
				return null;
			}

			$parent_view_name = trim( $file_headers[0] );

			return $this->engine->make($parent_view_name);

		}

		private function parseFileHeaders() : array {

			return array_filter( get_file_data(
				$this->filepath,
				[ self::PARENT_FILE_INDICATOR ]
			) );
		}

	}
