<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Support\Path;


	class PhpViewFinder implements ViewFinderInterface {

		/**
		 *
		 * Custom views to search in
		 *
		 * @param string[] $directories
		 */
		private $directories = [];

		/** @param string[] $directories */
		public function __construct( $directories = [] ) {

			$this->setDirectories( $directories );
		}

		/** @return string[] */
		public function getDirectories() : array {

			return $this->directories;
		}

		/**
		 * Set the custom views directories.
		 *
		 * @param  string[]  $directories
		 *
		 */
		public function setDirectories( array $directories ) :void {

			$this->directories = array_filter( array_map( [
				Path::class,
				'removeTrailingSlash',
			], $directories ) );
		}

		public function exists( string $view_name_name ) :bool {

			return ! empty( $this->resolveFilepath( $view_name_name ) );
		}

		public function filePath( string $view_name ) :string {

			return $this->resolveFilepath( $view_name );
		}

		/**
		 * Resolve a view to an absolute filepath.
		 *
		 * @param  string  $view_name
		 *
		 * @return string
		 */
		private function resolveFilepath( string $view_name ) : string {

			$file = $this->resolveFromAbsoluteFilepath( $view_name );

			if ( ! $file ) {
				$file = $this->resolveFromCustomDirectories( $view_name );
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

			$path = realpath( Path::normalize( $view_name ) );

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

				$file = Path::normalize( $directory . DIRECTORY_SEPARATOR . $view_name );

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
