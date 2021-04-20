<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\HasQueryFilterInterface;
	use WPEmerge\Contracts\RouteHandler;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\HasAttributesTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Helpers\RouteSignatureParameters;

	/**
	 * Represent a route
	 */
	class Route implements RouteInterface, HasQueryFilterInterface {

		use HasAttributesTrait;
		use HasQueryFilterTrait;

		private $resolved_arguments = null;

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

			if ( $this->resolved_arguments ) {

				return $this->resolved_arguments;

			}

			$condition = $this->getAttribute( 'condition' );

			if ( ! $condition instanceof ConditionInterface ) {
				throw new ConfigurationException( 'Route does not have a condition.' );
			}

			$condition_args = $condition->getArguments( $request );

			$this->updateArguments( $condition_args );

			return $condition_args;

		}

		public function arguments() : array {

			return $this->resolved_arguments;

		}

		public function updateArguments( array $arguments ) {

			$this->resolved_arguments = $arguments;

		}

		/**
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function setArguments( RequestInterface $request ) {

			$this->resolved_arguments = $this->getArguments( $request );

		}

		public function signatureParameters() {

			return RouteSignatureParameters::fromCallable(
				$this->handler()->raw()
			);

		}

		public function middleware() : array {

			return $this->getAttribute( 'middleware', [] );

		}

		private function handler() : RouteHandler {

			return $this->getAttribute( 'handler' );

		}

		public function run() {

			return function ( $request ) {

				$params = $this->signatureParameters();

				return $this->handler()->executeUsing(['request' => $request]);

			};

		}

	}
