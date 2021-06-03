<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Contracts\ContainerAdapter;
    use Psr\Http\Message\ResponseInterface;
    use Tests\unit\View\MethodField;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

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

            if ( $request->getMethod() !== 'POST' ) {
                return $next($request);
            }

            $request = $request->withMethod(
                $this->method_field->methodOverride($request)
            );

           $this->container->instance(Request::class, $request);

            return $next($request);

        }

    }