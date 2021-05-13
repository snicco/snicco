<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Http\Request;

    class TrueCondition implements ConditionInterface {


		public function isSatisfied( Request $request ) :bool {

			return true;
		}

		public function getArguments( Request $request ) :array {

			return [];

		}

	}