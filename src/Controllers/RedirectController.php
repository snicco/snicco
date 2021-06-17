<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class RedirectController extends Controller
    {


        public function to(...$args) : RedirectResponse
        {

            [$location, $status_code, $secure, $absolute] = array_slice($args, -4);

            return $this->response_factory->redirect()
                                          ->to($location, $status_code, [], $secure, $absolute);


        }

        public function away(...$args) : RedirectResponse
        {

            [$location, $status_code] = array_slice($args, -2);

            return $this->response_factory->redirect()
                                          ->away($location, $status_code);


        }

        public function toRoute(...$args) : RedirectResponse
        {

            [$route, $status_code, $params] = array_slice($args, -3);

            return $this->response_factory->redirect()
                                          ->toRoute($route, $status_code, $params);


        }

        public function exit(Request $request, MagicLink $magic_link)
        {

            $valid = $magic_link->hasValidRelativeSignature($request);

            if ( ! $valid) {

                return $this->response_factory->redirect()->home()->withHeader('Cache-Control', 'no-cache');

            }

            return $this->response_factory
                ->view('redirect-protection', [
                    'untrusted_url' => $request->query('intended_redirect'),
                    'home_url' => $this->url->toRoute('home'),
                ])
                ->withHeader('Cache-Control', 'no-cache');

        }

    }