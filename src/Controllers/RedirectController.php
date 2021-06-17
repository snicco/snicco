<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class RedirectController
    {

        /**
         * @var AbstractRedirector
         */
        private $redirector;

        public function __construct(AbstractRedirector $redirector)
        {

            $this->redirector = $redirector;
        }

        public function to(...$args) : RedirectResponse
        {

            [$location, $status_code, $secure, $absolute] = array_slice($args, -4);

            return $this->redirector->to($location, $status_code, [], $secure, $absolute);


        }

        public function away(...$args) : RedirectResponse
        {

            [$location, $status_code] = array_slice($args, -2);

            return $this->redirector->away($location, $status_code);


        }

        public function toRoute(...$args) : RedirectResponse
        {

            [$route, $status_code, $params] = array_slice($args, -3);

            return $this->redirector->toRoute($route, $status_code, $params);


        }

        public function exit(Request $request, MagicLink $magic_link, ResponseFactory $response_factory, UrlGenerator $generator)
        {

            $valid = $magic_link->hasValidRelativeSignature($request);

            if ( ! $valid) {

                return $this->redirector->home()->withHeader('Cache-Control', 'no-cache');

            }

            return $response_factory
                ->view('redirect-protection', [
                    'untrusted_url' => $request->query('intended_redirect'),
                    'home_url' => $generator->toRoute('home'),
                ])
                ->withHeader('Cache-Control', 'no-cache');

        }

    }