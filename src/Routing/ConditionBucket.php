<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing;

	use Codeception\Step\Condition;
	use Snicco\Support\Arr;

	class ConditionBucket {


		private $conditions = [];

		public function __construct(array $conditions = [])
        {
            $this->conditions = $conditions;
        }

        public static function createEmpty() : ConditionBucket {

			return new static([]);

		}

		public function add( $condition ) {

			$this->conditions[] = Arr::wrap( $condition );

		}

		public function all() : array {

			return $this->conditions;

		}

		public function combine( ConditionBucket $old_conditions ) : ConditionBucket {

			$this->mergePrevious( $old_conditions->all() );

			return $this;

		}

		private function mergePrevious( array $previous_group_conditions ) {

			$this->conditions = array_merge( $previous_group_conditions, $this->conditions );


		}

	}