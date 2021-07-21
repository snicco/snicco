<?php


    declare(strict_types = 1);


    namespace Snicco\Routing\Conditions;

    use Snicco\Contracts\UrlableInterface;
    use Snicco\ExceptionHandling\Exceptions\RouteLogicException;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;
    use Snicco\Routing\Route;
    use Snicco\Routing\Conditions\RequestAttributeCondition;
    use Snicco\Support\Arr;

    class AdminAjaxCondition extends RequestAttributeCondition implements UrlableInterface
    {

        public function isSatisfied(Request $request) : bool
        {

            return true;

        }

        public function getArguments(Request $request) : array
        {

            return [];

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