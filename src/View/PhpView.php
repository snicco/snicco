<?php



	namespace WPEmerge\View;

	use GuzzleHttp\Psr7\Response;
	use GuzzleHttp\Psr7\Utils;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Exceptions\ViewException;

	/**
	 * Render a view file with php.
	 */
	class PhpView implements ViewInterface {

		use HasNameTrait, HasContextTrait;

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
		 * @var ViewInterface|null
		 */
		private $layout;


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


		public function getLayout() : ?ViewInterface {

			return $this->layout;
		}


		public function setLayout( ?ViewInterface $layout ) : PhpView {

			$this->layout = $layout;

			return $this;
		}


		public function toString() {

			if ( empty( $this->getName() ) ) {
				throw new ViewException( 'View must have a name.' );
			}

			if ( empty( $this->getFilepath() ) ) {
				throw new ViewException( 'View must have a filepath.' );
			}

			$this->engine->pushLayoutContent( $this );

			if ( $this->getLayout() !== null ) {
				return $this->getLayout()->toString();
			}

			return $this->engine->getLayoutContent();
		}


		public function toResponse() {

			return ( new Response() )
				->withHeader( 'Content-Type', 'text/html' )
				->withBody( Utils::streamFor( $this->toString() ) );
		}

	}
