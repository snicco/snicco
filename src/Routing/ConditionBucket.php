<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Support\Arr;

	class ConditionBucket {


		private $conditions = [];

		public static function createEmpty() : ConditionBucket {

			return new static();

		}

		public function add($condition) {

			$this->conditions[] = Arr::wrap($condition);

		}

		public function all () : array {

			return $this->conditions;

		}

		public function combine ( ConditionBucket $old_bucket ) : ConditionBucket {

			$this->mergePrevious($old_bucket->all());

			return $this;

		}

		private function mergePrevious ( array $previous_group_conditions ) {

			$this->conditions = array_merge($previous_group_conditions, $this->conditions);

		}

	}