<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	/**
	 * Negate another condition's result.
	 */
	class NegateCondition implements ConditionInterface {

		/**
		 * Condition to negate.
		 *
		 * @var ConditionInterface
		 */
		protected $condition = null;

		/**
		 * Constructor.
		 *
		 *
		 * @param  ConditionInterface  $condition
		 */
		public function __construct( $condition ) {

			$this->condition = $condition;
		}


		public function isSatisfied( RequestInterface $request ) {

			return ! $this->condition->isSatisfied( $request );

		}


		public function getArguments( RequestInterface $request ) {

			return $this->condition->getArguments( $request );
		}

	}
