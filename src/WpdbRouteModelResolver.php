<?php


	namespace WPEmerge;

	use BetterWpdb\WpConnection;
	use Illuminate\Database\Eloquent\Model as EloquentModel;
	use Illuminate\Support\Collection;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Helpers\HandlerFactory;
	use WPEmerge\Support\Str;
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
		private function fetchModel( $value, $model, $column = 'id', array $parent_scope = []  ) {

			if ( ! count( $parent_scope ) ) {

				return $model::where( $column, $value )->firstOrFail();

			}

			// $model = $model::where( $column, $value )->firstOrFail();
			//
			// $parent_models = collect(($parent_scope))->keys();
			// $parent_columns = collect(($parent_scope))->values();
			//
			// $parent_model = $model->{$parent_models->first()}();
			//
			// $foo = 'bar';


		}

		private function bindRouteModels( array $parameters, array $model_blueprint ) : array {

			$models = collect($this->models);

			$model_blueprint = collect($model_blueprint);

			$model_blueprint = $model_blueprint->flatMap(function($value) {

				return Str::splitToKeyValuePair($value, ':');

			});

			if ( ! $models->count() ) {

				return $parameters;

			}

			$parameters = collect( $parameters );

			$same_type_hint = $models->intersectByKeys( $parameters );

			$model_values = $parameters->only( $same_type_hint->keys() );

			$build = $model_values->flatMap( function ( $parameter_value, $parameter_name ) use ( $same_type_hint, $model_blueprint ) {

				return [
					$parameter_name => [
						'value' => $parameter_value,
						'model' => $same_type_hint->get( $parameter_name ),
						'column' => $model_blueprint->get($parameter_name, 'id')
					],
				];

			} );

			$eloquent_models = $build->map( function ( $record ) use ($model_blueprint) {

				return $this->fetchModel(
					$record['value'],
					$record['model'],
					$record['column'],
					$model_blueprint->all()
				);

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

		public function allModelsCanBeResolved( $args, array $model_blueprint = [] ) {


			$this->models = $this->bindRouteModels( $args, $model_blueprint );

			return true;

		}

		public function models() {

			return $this->models;

		}

	}