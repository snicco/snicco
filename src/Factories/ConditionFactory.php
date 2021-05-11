<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RouteCondition;
	use WPEmerge\Routing\ConditionBlueprint;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\NegateCondition;
	use WPEmerge\Traits\ReflectsCallable;

	use function collect;

	class ConditionFactory {

		use ReflectsCallable;


		/**
		 * Registered condition types.
		 *
		 * @var array<string, string>
		 */
		private $condition_types;

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;


		public function __construct( array $condition_types, ContainerAdapter $container ) {

			$this->condition_types = $condition_types;
			$this->container       = $container;
		}

		public function compileConditions( RouteCondition $route ) : array {

			$conditions = collect( $route->getConditions() );

			$conditions = $conditions
				->map( function ( ConditionBlueprint $condition ) {

					if ( $compiled = $this->alreadyCompiled( $condition ) ) {

						return $compiled;

					}

					return $this->new( $condition );

				} )
				->unique();

			return $conditions->all();

		}

		private function new( ConditionBlueprint $blueprint ) {

			$type = $this->transformAliasToClassName($blueprint->type());
			$conditions_arguments = $blueprint->args();

			if ( $type === $this->condition_types[ConditionBlueprint::NEGATES_WORD] ) {

				return $this->newNegated( $blueprint, $conditions_arguments );

			}

			$args = $this->buildNamedConstructorArgs(
				$type,
				$conditions_arguments
			);

			return $blueprint->instance() ?? $this->container->make( $type, $args );


		}

		private function transformAliasToClassName( string $type ) {

			return $this->condition_types[$type] ?? $type;

		}

		private function alreadyCompiled( ConditionBlueprint $condition ) : ?object {

			if ( $condition->type() === ConditionBlueprint::NEGATES_WORD ) {

				return null;

			}

			return $condition->instance();

		}

		private function newNegated( ConditionBlueprint $blueprint, array $args ) : NegateCondition {

			$instance = $blueprint->instance();

			if ( $instance instanceof ConditionInterface) {

				return new NegateCondition( $instance );

			}

			if ( is_callable( $instance ) ) {

				return new NegateCondition( new CustomCondition( $instance, ...$args ) );

			}

			if ( is_callable( $negates = $blueprint->negates() ) ) {


				return new NegateCondition( new CustomCondition( $negates, ...$args ) );

			}

			$instance =  $this->container->make(

				$type = $this->transformAliasToClassName( $negates ),
				$this->buildNamedConstructorArgs( $type, $args )

			);

			return new NegateCondition($instance);


		}


	}
