<?php


	namespace WPEmerge;

	use BetterWpdb\WpConnection;
	use Illuminate\Database\Eloquent\Model as EloquentModel;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Traits\ReflectsCallable;

	class WpdbRouteModelResolver implements RouteModelResolver {

		use ReflectsCallable;

		/**
		 * @var \BetterWpdb\WpConnection
		 */
		private $connection;

		public function __construct(WpConnection $connection) {

			$this->connection = $connection;

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
		private function fetchModel ( $value, $model, $column = 'id') {

			return $model::where($column, $value )->firstOrFail();

		}

		public function bindRouteModels( $callable, array $parameters ) : array {

			$method_parameters = $this->getCallReflector( Arr::wrap( $callable ) )->getParameters();

			$models = $this->findModels( $method_parameters );

			if ( ! $models->count() ) {

				return $parameters;

			}

			$parameters = collect ($parameters);

			$same_type_hint = $models->intersectByKeys($parameters);

			$model_values = $parameters->only($same_type_hint->keys());


			$build = $model_values->flatMap(function ($parameter_value, $parameter_name) use ($same_type_hint) {

				return [ $parameter_name => [
					'value' => $parameter_value,
					'model' => $same_type_hint->get($parameter_name)
				]
				];

			});

			$eloquent_models = $build->map(function ($record) {

				return $this->fetchModel($record['value'], $record['model']);

			});

			return $parameters->merge($eloquent_models)->all();


		}

		private function findModels( $method_parameters ) : Collection {

			/** @var \ReflectionParameter $parameter */
			$method_parameters = collect( $method_parameters );

			return $method_parameters->flatMap( function ( $parameter ) {

				$name = $parameter->getName();
				$type = $parameter->getType()->getName();

				return [ $name => $type ];

			} )->filter( function ( $class ) {

				return $this->isEloquentModel( $class );

			} );


		}

		private function isEloquentModel( $class_name ) {

			return is_subclass_of( $class_name, EloquentModel::class );

		}

	}