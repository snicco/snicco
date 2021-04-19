<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	class ModelCondition implements ConditionInterface {


		/**
		 * @var array
		 */
		private $model_blueprint;

		public function __construct(array $model_blueprint) {

			$this->model_blueprint = $model_blueprint;

		}

		// We handle this at the middleware level.
		public function isSatisfied( RequestInterface $request ) : bool {

			return true;

		}

		public function getArguments( RequestInterface $request ) : array {

			return ['model_blueprint' => $this->model_blueprint];


		}

	}