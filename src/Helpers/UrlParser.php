<?php


	namespace WPEmerge\Helpers;

	use WPEmerge\Support\Str;

	class UrlParser {

		public const default_key = 'id';

		public static function parseModelsFromUrl( string $url_pattern ) {

			preg_match_all('/[^{]+(?=})/', $url_pattern, $matches);

			$matches = collect($matches)->flatten();

			$model_blueprint = $matches->flatMap(function ($value) {

				$key = static::containsDot($value) ? Str::after($value, ':') : static::default_key;

				return [Str::before($value, ':') =>  $key];

			});

			return $model_blueprint->all();

		}

		public static function normalize( string $url ) : string {

			while ( Str::contains( $url, ':' ) ) {

				// $column_id = Str::firstBetweenDot( $url, '{', '}' );
				//
				// $this->model_columns[] = $column_id;

				$before = Str::before( $url, ':' );

				$rest = Str::replaceFirst( $before, '', $url );

				$column = Str::before( $rest, '}' );

				$url = $before . Str::replaceFirst( $column, '', $rest );

			}

			return $url;

		}

		private static function containsDot ($string) {

			return Str::contains($string, ':');

		}

	}