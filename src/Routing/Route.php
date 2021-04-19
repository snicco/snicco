<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\HasQueryFilterInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\HasAttributesTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ConditionInterface;

	/**
	 * Represent a route
	 */
	class Route implements RouteInterface, HasQueryFilterInterface {

		use HasAttributesTrait;
		use HasQueryFilterTrait;


		public function isSatisfied( RequestInterface $request ) {

			$methods   = $this->getAttribute( 'methods', [] );
			$condition = $this->getAttribute( 'condition' );

			if ( ! in_array( $request->getMethod(), $methods ) ) {
				return false;
			}

			if ( ! $condition instanceof ConditionInterface ) {
				throw new ConfigurationException( 'Route does not have a condition.' );
			}

			return $condition->isSatisfied( $request );
		}


		public function getArguments( RequestInterface $request ) {

			$condition = $this->getAttribute( 'condition' );

			if ( ! $condition instanceof ConditionInterface ) {
				throw new ConfigurationException( 'Route does not have a condition.' );
			}

			return $condition->getArguments( $request );
		}

	}
