<?php


	namespace WPEmerge;

	use BetterWpdb\WpConnection;
	use Illuminate\Database\Eloquent\Model as EloquentModel;
	use Illuminate\Support\Collection;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Helpers\HandlerFactory;
	use WPEmerge\Traits\ReflectsCallable;

	class WpdbRouteModelResolver implements RouteModelResolver {

		use ReflectsCallable;

		/**
		 * @var \BetterWpdb\WpConnection
		 */
		private $connection;

		/**
		 * @var \WPEmerge\Helpers\HandlerFactory
		 */
		private $factory;

		/**
		 * @var array
		 */
		private $models;

		public function __construct( WpConnection $connection, HandlerFactory $factory) {

			$this->connection = $connection;
			$this->factory = $factory;

		}

		/**
		 * @param $value
		 * @param $model
		 * @param  string  $column
		 *
		 * @return mixed
		 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
		 * @see \WPEmerge\Traits\ResolvesRouteModels::bindRouteModels()
		 */
		private function fetchModel( $value, $model, $column = 'id' ) {

			return $model::where( $column, $value )->firstOrFail();

		}

		private function bindRouteModels( array $parameters ) : array {

			$models = collect($this->models);

			if ( ! $models->count() ) {

				return $parameters;

			}

			$parameters = collect( $parameters );

			$same_type_hint = $models->intersectByKeys( $parameters );

			$model_values = $parameters->only( $same_type_hint->keys() );

			$build = $model_values->flatMap( function ( $parameter_value, $parameter_name ) use ( $same_type_hint ) {

				return [
					$parameter_name => [
						'value' => $parameter_value,
						'model' => $same_type_hint->get( $parameter_name ),
					],
				];

			} );

			$eloquent_models = $build->map( function ( $record ) {

				return $this->fetchModel( $record['value'], $record['model'] );

			} );

			return $parameters->merge( $eloquent_models )->all();

		}

		private function findModels( array $method_parameters ) : array {

			/** @var \ReflectionParameter $parameter */
			$method_parameters = collect( $method_parameters );

			return $method_parameters->flatMap( function ( $parameter ) {

				$name = $parameter->getName();
				$type = $parameter->getType();

				return [ $name => $type ? $type->getName() : '' ];

			} )->filter( function ( $class ) {

				return $this->isEloquentModel( $class );

			} )->all();


		}

		private function isEloquentModel( $class_name ) {

			$class_name = strval($class_name);

			return class_exists($class_name) && is_subclass_of( $class_name, EloquentModel::class );

		}

		public function expectsEloquent( $handler ) : bool {

			if ( $handler instanceof \Closure ) {
				return false;
			}

			$handler = $this->factory->make($handler)->fqnCallable();

			$method_parameters = $this->getCallReflector([$handler]);

			$this->models = $this->findModels( $method_parameters->getParameters() );

			return count($this->models) > 0;

		}

		public function allModelsCanBeResolved( $args ) {


			$this->models = $this->bindRouteModels( $args );

			return true;

		}

		public function models() {

			return $this->models;

		}

	}