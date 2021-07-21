<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Confirmation;

    use Illuminate\Support\InteractsWithTime;
    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Auth\Mail\ConfirmAuthMail;
    use Snicco\Contracts\MagicLink;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Support\WP;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;
    use Snicco\Http\ResponseFactory;
    use Snicco\Mail\MailBuilder;
    use Snicco\Session\Session;
    use Snicco\View\ViewFactory;

    class EmailAuthConfirmation implements AuthConfirmation
    {

        /**
         * @var ViewFactory
         */
        private $view_factory;

        /**
         * @var MagicLink
         */
        private $magic_link;

        /**
         * @var UrlGenerator
         */
        private $url;

        public function __construct(MagicLink $magic_link, ViewFactory $view_factory, UrlGenerator $url)
        {

            $this->magic_link = $magic_link;
            $this->view_factory = $view_factory;
            $this->url = $url;
        }

        public function confirm(Request $request)
        {

            $valid = $this->magic_link->hasValidSignature($request, true);

            if ( ! $valid ) {

                return ['message' => 'Confirmation link invalid or expired.'];

            }

            $this->magic_link->invalidate($request->fullUrl());

            return true;

        }

        public function viewResponse(Request $request)
        {

            return $this->view_factory->make('auth-layout')
                                      ->with([
                                          'view' => 'auth-confirm-via-email',
                                          'post_to' => $this->url->toRoute('auth.confirm.email')
                                      ]);

        }


    }