<?php


	declare( strict_types = 1 );


	namespace WPMvc\Routing\Conditions;

	use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Http\Psr7\Request;

    class NegateCondition implements ConditionInterface {

		/**
		 * Condition to negate.
		 *
		 * @var ConditionInterface
		 */
		private $condition;


		public function __construct( ConditionInterface $condition ) {

			$this->condition = $condition;

		}


		public function isSatisfied( Request $request ) : bool {

			return ! $this->condition->isSatisfied( $request );

		}


		public function getArguments( Request $request ) : array {

			return $this->condition->getArguments( $request );
		}

	}
