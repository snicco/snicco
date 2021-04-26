<?php


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Helpers\MixedType;


	class PhpViewFilesystemFinder implements ViewFinderInterface {

		/**
		 * Custom views directories to check first.
		 *
		 * @var string[]
		 */
		private $directories = [];

		/**
		 * Constructor.
		 *
		 *
		 * @param  string[]  $directories
		 */
		public function __construct( $directories = [] ) {

			$this->setDirectories( $directories );
		}

		/**
		 * Get the custom views directories.
		 *
		 * @return string[]
		 */
		public function getDirectories() : array {

			return $this->directories;
		}

		/**
		 * Set the custom views directories.
		 *
		 *
		 * @param  string[]  $directories
		 *
		 * @return void
		 */
		public function setDirectories( array $directories ) {

			$this->directories = array_filter( array_map( [
				MixedType::class,
				'removeTrailingSlash',
			], $directories ) );
		}

		public function exists( string $view_name ) :bool {

			return ! empty( $this->resolveFilepath( $view_name ) );
		}

		public function filePath( string $view_name ) :string {

			return $this->resolveFilepath( $view_name );
		}

		/**
		 * Resolve a view to an absolute filepath.
		 *
		 * @param  string  $view
		 *
		 * @return string
		 */
		private function resolveFilepath( $view ) : string {

			$file = $this->resolveFromAbsoluteFilepath( $view );

			if ( ! $file ) {
				$file = $this->resolveFromCustomDirectories( $view );
			}

			return $file;
		}

		/**
		 * Resolve a view if it is a valid absolute filepath.
		 *
		 * @param  string  $view_name
		 *
		 * @return string
		 */
		private function resolveFromAbsoluteFilepath( string $view_name ) : string {

			$path = realpath( MixedType::normalizePath( $view_name ) );

			if ( ! empty( $path ) && ! is_file( $path ) ) {
				$path = '';
			}

			return $path ? $path : '';
		}

		/**
		 * Resolve a view if it exists in the custom views directories.
		 *
		 * @param  string  $view_name
		 *
		 * @return string
		 */
		private function resolveFromCustomDirectories( string $view_name ) : string {

			$directories = $this->getDirectories();

			foreach ( $directories as $directory ) {

				$file = MixedType::normalizePath( $directory . DIRECTORY_SEPARATOR . $view_name );

				if ( ! is_file( $file ) ) {
					// Try adding a .php extension.
					$file .= '.php';
				}

				$file = realpath( $file );

				if ( $file && is_file( $file ) ) {
					return $file;
				}
			}

			return '';
		}

	}
