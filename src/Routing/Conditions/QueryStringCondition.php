<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;

	class QueryStringCondition implements ConditionInterface, UrlableInterface {

		/**
		 * @var array
		 */
		private $query_string_arguments;

		public function __construct($query_string_arguments) {

			$this->query_string_arguments = collect($query_string_arguments);

		}

		/**
		 *
		 * @return bool|void
		 */
		public function isSatisfied( RequestInterface $request ) {

			$query_strings = $request->query();

			$failed = $this->query_string_arguments->reject(function ($value , $query_string  ) use ( $query_strings ) {

				if  ( ! in_array( $query_string, array_keys($query_strings) ) ) {

					return true;

				}

				return $value === $query_strings[$query_string];

			});

			return $failed->isEmpty();

			/**
			 *
			 * @todo Improve this. See how RouteMatcher handles conditions and aborts on first match.
			 *
			 */


		}

		public function getArguments( RequestInterface $request ) {

			$values = collect($request->query())->only($this->query_string_arguments->keys())->all();

			return $values;

		}

		public function toUrl( $arguments = [] ) {
			// TODO: Implement toUrl() method.
		}

	}