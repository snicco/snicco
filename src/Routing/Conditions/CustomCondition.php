<?php


	declare( strict_types = 1 );


	namespace WPMvc\Routing\Conditions;

	use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Http\Psr7\Request;

    class CustomCondition implements ConditionInterface {


		/** @var callable|string  */
		private $callable;

		private $arguments;

		public function __construct( callable $callable, ...$args  ) {

			$this->callable  = $callable;
			$this->arguments = $args;

		}

		public function getCallable()
        {
		    return $this->callable;
        }

        public function setCallable($callable) {
		    $this->callable = $callable;
        }

		public function isSatisfied( Request $request ) :bool {

			return call_user_func_array( $this->callable, $this->arguments );

		}


		public function getArguments( Request $request ) : array {

			return $this->arguments;

		}

	}
