<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

	use BetterWP\Contracts\ConditionInterface;
    use BetterWP\Http\Psr7\Request;

    class TrueCondition implements ConditionInterface {


		public function isSatisfied( Request $request ) :bool {

			return true;
		}

		public function getArguments( Request $request ) :array {

			return [];

		}

	}