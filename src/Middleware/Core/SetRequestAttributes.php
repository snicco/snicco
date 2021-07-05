<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Support\WP;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;

    class SetRequestAttributes extends Middleware
    {

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $request = $request
                ->withUserId(WP::userId());

            return $next($request);

        }

    }