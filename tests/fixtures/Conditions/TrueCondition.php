<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

	use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Http\Psr7\Request;

    class TrueCondition implements ConditionInterface {


		public function isSatisfied( Request $request ) :bool {

			return true;
		}

		public function getArguments( Request $request ) :array {

			return [];

		}

	}