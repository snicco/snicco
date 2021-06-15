<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\ExceptionHandling\Exceptions\RouteLogicException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\Conditions\RequestAttributeCondition;
    use WPEmerge\Support\Arr;

    class AdminAjaxCondition extends RequestAttributeCondition implements UrlableInterface
    {

        public function isSatisfied(Request $request) : bool
        {

            return true;

            // return parent::isSatisfied($request)
            //     || $request->query('action') === $this->expectedAction();


        }

        public function getArguments(Request $request) : array
        {

            return [];

            $parent = parent::getArguments($request);

            return ( count( $parent ) ) ? $parent : Arr::wrap($request->query('action', []));


        }

        private function expectedAction() : ?string
        {

            return $this->request_arguments->get('action', '');

        }

        public function toUrl(array $arguments = []) : string
        {

            $method = strtoupper(Arr::get($arguments, 'method', 'POST'));

            $base_url = WP::adminUrl('admin-ajax.php');

            if ($method !== 'GET') {

                return $base_url;

            }

            /** @var Route $route */
            $route = $arguments['route'];

            if ( ! in_array('GET', $route->getMethods() ) ) {

                throw new RouteLogicException(
                    'Route: '.$route->getName().'does not respond to GET requests'
                );

            }

            return WP::addQueryArg('action', $this->expectedAction(), $base_url);


        }

    }