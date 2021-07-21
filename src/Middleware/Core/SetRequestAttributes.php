<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Support\WP;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;

    class SetRequestAttributes extends Middleware
    {

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $request = $request
                ->withUserId(WP::userId());

            return $next($request);

        }

    }