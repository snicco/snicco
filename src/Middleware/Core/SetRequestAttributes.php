<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Validation\Validator;

    class SetRequestAttributes extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            $request = $request
                ->withUser(WP::userId())
                ->withAttribute('_wp_admin_folder', WP::wpAdminFolder());

            return $next($request);

        }

    }