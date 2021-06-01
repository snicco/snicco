<?php


    declare(strict_types = 1);


    namespace WPEmerge\Controllers;

    use Illuminate\Contracts\Support\MessageProvider;
    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class ConfirmAuthenticationController
    {

        /**
         * @var ViewFactory
         */
        private $view;
        /**
         * @var UrlGenerator
         */
        private $url_generator;
        /**
         * @var ResponseFactory
         */
        private $response_factory;

        public function __construct(ViewFactory $view, UrlGenerator $url_generator, ResponseFactory $response_factory)
        {

            $this->view = $view;
            $this->url_generator = $url_generator;
            $this->response_factory = $response_factory;

        }

        public function show()
        {

            $post_url = $this->url_generator->toRoute('auth.confirm.send', [], true, false);

            WP::logout();

            return $this->view->make('auth-confirm')->with('post_url', $post_url);

        }

        public function send(Request $request, SessionStore $session) {

            $email = Arr::get($request->getParsedBody(), 'email', '');

            $user = get_user_by('email', $email);

            if ( ! $user instanceof \WP_User ) {

                $bag = new MessageBag();
                $bag->add('email', 'The email you entered is not linked with any account.');
                $errors = $session->get('errors', new ViewErrorBag);
                $session->flash(
                    'errors', $errors->put('default', $bag )
                );

                $session->flashInput(['email' => $email]);

                return $this->response_factory->redirect(404)->to($request->getFullUrl());

            }

            $session->flashInput(['email' => $email]);
            $session->flash('auth.confirm.success', 'Success: A confirmation email was send to: ' . $email);

            return $this->response_factory->redirect(200)->to($request->getFullUrl());



        }

        protected function parseErrors($provider)
        {
            if ($provider instanceof MessageProvider) {
                return $provider->getMessageBag();
            }

            return new MessageBag((array) $provider);
        }


    }