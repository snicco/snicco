<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    class SetRequestAttributes extends Middleware
    {

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $request = $request
                ->withUser(WP::currentUser())
                ->withAttribute('_wp_admin_folder', WP::wpAdminFolder());

            return $next($request);

        }

    }