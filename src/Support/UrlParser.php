<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;

	use WPEmerge\Support\Str;

	use function collect;

	class UrlParser {

		public const default_key = 'id';

		public static function parseModelsFromUrl( string $url_pattern ) : array {

			preg_match_all( '/[^{]+(?=})/', $url_pattern, $matches );

			$matches = collect( $matches )->flatten();

			$model_blueprint = $matches->flatMap( function ( $value ) {

				$key = static::containsDot( $value ) ? Str::after( $value, ':' ) : static::default_key;

				return [ Str::before( $value, ':' ) => $key ];

			} );

			return $model_blueprint->all();

		}

		public static function normalize( string $url ) : string {

			while ( Str::contains( $url, ':' ) ) {

				$before = Str::before( $url, ':' );

				$rest = Str::replaceFirst( $before, '', $url );

				$column = Str::before( $rest, '}' );

				$url = $before . Str::replaceFirst( $column, '', $rest );

			}

			return $url;

		}

		private static function containsDot( $string ) : bool {

			return Str::contains( $string, ':' );

		}

		public static function requiredSegments( string $url_pattern ) : array {

			preg_match_all( '/[^{]+\w(?=})/', $url_pattern, $matches );

			return collect( $matches )->flatten()->all();

		}

		public static function segments( string $url_pattern ) : array {

			preg_match_all( '/[^{]+(?=})/', $url_pattern, $matches );

			return collect( $matches )->flatten()->all();

		}

		public static function isDynamic( string $url ) : bool {

			$result = preg_match( '/[^{]+(?=})/', $url, $matches );

			return $result === 1;
		}

		public static function isStaticUrl( string $url ) : bool {

			return ! self::isDynamic( $url );
		}

		public static function optionalSegments( string $url_pattern ) {

			// preg_match_all( '/[^{]+[?](?=})/', $url_pattern, $matches );
			preg_match_all( '/[^\/{]+[?]/', $url_pattern, $matches );

			return collect( $matches )->flatten()->all();


		}

		public static function replaceOptionalMatch( string $url_pattern ) : array {


			preg_match_all( '/(\/{[^\/{]+[?]})/', $url_pattern, $matches );

			return collect( $matches )->flatten()->unique()->all();

		}


	}