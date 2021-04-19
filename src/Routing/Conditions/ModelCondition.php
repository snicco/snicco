<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteModelResolver;

	class ModelCondition extends UrlCondition {


		private $model_blueprint;

		private $handler;

		/**
		 * @var \WPEmerge\Contracts\RouteModelResolver
		 */
		private $model_resolver;

		public function __construct( RouteModelResolver $model_resolver,  $model_blueprint,  $handler,  $url,  $where = [] ) {

			parent::__construct( $url, $where );

			$this->model_blueprint = $model_blueprint;
			$this->handler = $handler;
			$this->model_resolver = $model_resolver;

		}

		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! parent::isSatisfied( $request ) ) {

				return false;

			}

			if ( ! $this->expectsEloquentModels() ) return true;

			return $this->allModelsResolved();


		}

		public function getArguments( RequestInterface $request ) {

			return $this->models() + parent::getArguments( $request );
		}

		private function allModelsResolved() {

			return true;

		}

		private function models() {

			return [];

		}

		private function expectsEloquentModels() {

			return false;

		}


	}