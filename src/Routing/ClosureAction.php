<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing;

	use Closure;
    use Snicco\Contracts\RouteAction;

    class ClosureAction implements RouteAction
    {

        private Closure $resolves_to;
        private Closure $executable_closure;

        public function __construct(Closure $raw_closure, Closure $executable_closure)
        {

            $this->resolves_to = $raw_closure;
            $this->executable_closure = $executable_closure;

        }

        public function executeUsing(...$args)
        {

			$closure = $this->executable_closure;

			return $closure(...$args);

		}

		public function raw () {

			return $this->resolves_to;

		}



	}