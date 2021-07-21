<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;

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
		public function isSatisfied( Request $request ) :bool {

            $query_args = $request->getQueryParams();

            foreach ( $this->query_string_arguments as $key => $value ) {

                if ( ! in_array($key, array_keys($query_args), true ) ) {

                    return false;

                }

            }

            $failed_value = $this->query_string_arguments->first(function ($value, $key) use ($query_args) {

                return $value !== $query_args[$key];

            });

            return $failed_value === null;



		}

		public function getArguments( Request $request ) : array
        {

            return collect($request->getQueryParams())->only($this->query_string_arguments->keys())->all();

		}




	}