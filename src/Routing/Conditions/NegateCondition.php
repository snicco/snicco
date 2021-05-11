<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;


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


		public function isSatisfied( RequestInterface $request ) : bool {

			return ! $this->condition->isSatisfied( $request );

		}


		public function getArguments( RequestInterface $request ) : array {

			return $this->condition->getArguments( $request );
		}

	}
