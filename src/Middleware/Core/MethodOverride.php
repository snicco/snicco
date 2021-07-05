<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware\Core;

    use Contracts\ContainerAdapter;
    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\View\MethodField;

    class MethodOverride extends Middleware
    {

        /**
         * @var MethodField
         */
        private $method_field;

        /**
         * @var ContainerAdapter
         */
        private $container;

        public function __construct(MethodField $method_field, ContainerAdapter $container)
        {
            $this->method_field = $method_field;
            $this->container = $container;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            if ( $request->getMethod() !== 'POST' || ! $request->filled('_method') ) {
                return $next($request);
            }

            $signature = $request->post('_method');

            if ( ! $method = $this->method_field->validate($signature) ) {

                return $next($request);

            }

            $request = $request->withMethod($method);

            $this->container->instance(Request::class, $request);

            return $next($request);

        }

    }