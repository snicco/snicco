<?php


	namespace WPEmerge\Traits;

	use Illuminate\Database\Eloquent\Model as EloquentModel;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;

	trait ResolvesRouteModels {

		use ReflectsCallable;

		private function bindRouteModels( $callable, array $parameters ) : array {

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

				return $this->model_resolver->fetchModel($record['value'], $record['model']);

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