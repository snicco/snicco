<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	class UniqueCondition implements ConditionInterface {


		public function isSatisfied( RequestInterface $request ) : bool {

			$count = $GLOBALS['test']['unique_condition'] ?? 0;

			$count ++;

			$GLOBALS['test']['unique_condition'] = $count;

			return true;

		}

		public function getArguments( RequestInterface $request ) : array {

			return [];

		}

	}