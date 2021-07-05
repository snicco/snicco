<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Support\WP;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;

    class SetRequestAttributes extends Middleware
    {

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $request = $request
                ->withUserId(WP::userId());

            return $next($request);

        }

    }