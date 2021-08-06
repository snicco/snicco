<?php


	declare( strict_types = 1 );


	namespace Snicco\View;

	use Snicco\Contracts\PhpViewInterface;
    use Snicco\Contracts\ViewInterface;
    use Snicco\Support\Arr;
    use Snicco\Support\WP;

    class PhpView implements PhpViewInterface
    {

        /**
         * Name of view file header based on which to resolve parent views.
         *
         * @var string
         */
        public const PARENT_FILE_INDICATOR = 'Layout';

        private PhpViewEngine $engine;
        private string        $filepath;
        private ?PhpView      $parent_view;
        private array         $context = [];
        private string        $name;

        public function __construct(PhpViewEngine $engine, string $name, string $path)
        {

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

		public function toResponsable() : string {

			return $this->toString();

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
		 * @return ViewInterface|PhpView|null
		 */
		private function parseParentView() : ?PhpView {

			if ( empty( $file_headers = $this->parseFileHeaders() ) ) {
				return null;
			}

			$parent_view_name = trim( $file_headers[0] );

			return $this->engine->make($parent_view_name);

		}

		private function parseFileHeaders() : array {

			return array_filter( WP::fileHeaderData(
				$this->filepath,
				[ self::PARENT_FILE_INDICATOR ]
			) );
		}

	}
