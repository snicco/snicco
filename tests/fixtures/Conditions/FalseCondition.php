<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

    use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Http\Psr7\Request;

	class FalseCondition implements ConditionInterface {


		public function isSatisfied( Request $request ) :bool {

			return false;
		}

		public function getArguments( Request $request ) : array {

			return [];

		}

	}