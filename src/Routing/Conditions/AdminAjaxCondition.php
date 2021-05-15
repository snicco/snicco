<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Contracts\UrlableInterface;
    use WPEmerge\Exceptions\RouteLogicException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Route;
    use WPEmerge\ServiceProviders\RequestAttributeCondition;
    use WPEmerge\Support\Arr;

    class AdminAjaxCondition extends RequestAttributeCondition implements UrlableInterface
    {

        public function isSatisfied(Request $request) : bool
        {

            return parent::isSatisfied($request)
                || $request->query('action') === $this->expectedAction();


        }

        public function getArguments(Request $request) : array
        {

            return array_merge(
                parent::getArguments($request),
                [$request->query('action', [])]
            );
        }

        private function expectedAction() : ?string
        {

            return $this->request_arguments->get('action', '');

        }

        public function toUrl($arguments = []) : string
        {

            $method = strtoupper(Arr::get($arguments, 'method', 'POST'));

            $base_url = WP::adminUrl('admin-ajax.php');

            if ($method !== 'GET') {

                return $base_url;

            }

            /** @var Route $route */
            $route = $arguments['route'];

            if ( ! in_array('GET', $route->getMethods())) {

                throw new RouteLogicException(
                    'Route: '.$route->getName().'does not respond to GET requests'
                );

            }

            return WP::addQueryArg('action', $this->expectedAction(), $base_url);


        }

    }