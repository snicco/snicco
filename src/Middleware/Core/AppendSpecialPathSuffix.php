<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Request;

    /**
     *
     * This middleware is needed to allow matching of wordpress admin pages
     * with the normal route api.
     * Since admin pages in WP have the following format: /wp-admin/admin.php?page=test
     * the psr7 slug would be wp-admin/admin.php.
     *
     * In order to allow users to match these routes like this:
     *
     * $router->get('admin/test') we need to apply the query string value of page to
     * the psr7 slug.
     *
     */
    class AppendSpecialPathSuffix extends Middleware
    {

        public function handle(Request $request, Delegate $next)
        {

            $uri = $request->getUri();

            $new_path = $this->appendToPath($request);

            $new_uri = $uri->withPath($new_path);

            return $next( $request->withUri($new_uri) );

        }


        private function appendToPath(Request $request) : string
        {

            $path = $request->path();

            if (WP::isAdmin() && ! WP::isAdminAjax()) {

                $path = $path.'/'.$request->query('page', '');

            }

            if (WP::isAdminAjax()) {

                $path = $path.'/'.$request->parsedBody('action', $request->query('action', ''));

            }

            return $path;
        }



    }