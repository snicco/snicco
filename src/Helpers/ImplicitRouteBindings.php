<?php


	namespace WPEmerge\Helpers;

	use Contracts\ContainerAdapter;
	use Illuminate\Contracts\Routing\UrlRoutable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Support\Str;
	use ReflectionParameter;
	use WPEmerge\Contracts\RouteCondition;
	use Illuminate\Database\Eloquent\ModelNotFoundException;

	class ImplicitRouteBindings {


		/**
		 * Resolve the implicit route bindings for the given route.
		 *
		 * @param  ContainerAdapter  $container
		 * @param  RouteCondition  $route
		 *
		 * @return void
		 *
		 * @throws ModelNotFoundException
		 */
		public static function resolveForRoute( ContainerAdapter $container, RouteCondition $route ) {

			$arguments  = collect($route->arguments());

			$model_blue_print = $arguments->pull('model_blueprint');

			$sign_params = collect($route->signatureParameters());

			$models_signatures = $sign_params->filter(function ($parameter) {

				return Reflector::isParameterSubclassOf( $parameter, UrlRoutable::class );

			});

			if ( ! count($models_signatures) ) {

				$route->updateArguments($arguments->all());

				return;
			}

			foreach ( $sign_params as $parameter ) {

				if ( ! $parameterName = static::getParameterName( $parameter->getName(), $parameters ) ) {
					continue;
				}

				$parameterValue = $parameters[ $parameterName ];

				if ( $parameterValue instanceof UrlRoutable ) {
					continue;
				}

				$instance = $container->make( Reflector::getParameterClassName( $parameter ) );

				$parent = $route->parentOfParameter( $parameterName );

				if ( $parent instanceof UrlRoutable && in_array( $parameterName, array_keys( $route->bindingFields() ) ) ) {
					if ( ! $model = $parent->resolveChildRouteBinding(
						$parameterName, $parameterValue, $route->bindingFieldFor( $parameterName )
					) ) {
						throw ( new ModelNotFoundException )->setModel( get_class( $instance ), [ $parameterValue ] );
					}
				} elseif ( ! $model = $instance->resolveRouteBinding( $parameterValue, $route->bindingFieldFor( $parameterName ) ) ) {

					throw ( new ModelNotFoundException )->setModel( get_class( $instance ), [ $parameterValue ] );

				}

				$route->setParameter( $parameterName, $model );
			}

		}


		/**
		 * Return the parameter name if it exists in the given parameters.
		 *
		 * @param  string  $name
		 * @param  array  $parameters
		 *
		 * @return string|null
		 */
		private static function getParameterName( string $name, array $parameters) : ?string {

			if (array_key_exists( $name, $parameters )) {
				return $name;
			}

			if (array_key_exists($snakedName = Str::snake($name), $parameters)) {
				return $snakedName;
			}
		}

	}