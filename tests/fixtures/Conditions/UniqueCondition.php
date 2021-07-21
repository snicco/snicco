<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;

    class UniqueCondition implements ConditionInterface {


		public function isSatisfied( Request $request ) : bool {

			$count = $GLOBALS['test']['unique_condition'] ?? 0;

			$count ++;

			$GLOBALS['test']['unique_condition'] = $count;

			return true;

		}

		public function getArguments( Request $request ) : array {

			return [];

		}

	}