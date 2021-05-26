<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware\Core;

    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;

    /**
     *
     * This middleware is needed to allow matching of wordpress admin pages
     * with the normal route api.
     * Since admin pages in WP have the following format: /wp-admin/options-general.php?page=foobar
     * the psr7 slug would be wp-admin/admin.php.
     *
     * In order to allow users to match these routes like this:
     *
     * $router->get('options/foobar') we need to apply the query string value of page to
     * the psr7 slug.
     *
     * For ajax requests to wp-admin/ajax.php we do the same thing but instead we append the
     * action to the url from either the parsed body or the query string.
     *
     * This allows users to create ajax routes like this without needing to use conditions:
     *
     * $router->post('my_action')
     *
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