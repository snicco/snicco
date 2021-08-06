<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;

    class NegateCondition implements ConditionInterface {

		private ConditionInterface $condition;

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
