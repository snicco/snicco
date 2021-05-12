<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;

	class QueryStringCondition implements ConditionInterface{

		/**
		 * @var array
		 */
		protected $query_string_arguments;

		public function __construct($query_string_arguments) {

			$this->query_string_arguments = collect($query_string_arguments);

		}

		/**
		 *
		 * @return bool|void
		 */
		public function isSatisfied( RequestInterface $request ) {

            $query_args = $request->query();

            foreach ( $this->query_string_arguments as $key => $value ) {

                if ( ! in_array($key, array_keys($query_args) ) ) {

                    return false;

                }

            }

            $failed_value = $this->query_string_arguments->first(function ($value, $key) use ($query_args) {

                return $value !== $query_args[$key];

            });

            return $failed_value === null;



		}

		public function getArguments( RequestInterface $request ) : array
        {

            return collect($request->query())->only($this->query_string_arguments->keys())->all();

		}




	}