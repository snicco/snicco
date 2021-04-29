<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Support\Arr;

	class ConditionBucket {


		private $conditions = [];

		public function add($condition) {

			$this->conditions[] = Arr::wrap($condition);

		}

		public function all () {

			return $this->conditions;

		}

		public function combine ( ConditionBucket $old_bucket ) : ConditionBucket {

			$this->pushOld($old_bucket->all());

			return $this;

		}

		private function pushOld ( array $conditions ) {

			$this->conditions = array_merge($conditions, $this->conditions);


		}

	}