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

	}