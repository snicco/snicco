<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Confirmation;

    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Contracts\MagicLink;
    use Snicco\Http\Psr7\Request;
    use Snicco\Routing\UrlGenerator;
    use Snicco\View\ViewFactory;

    class EmailAuthConfirmation implements AuthConfirmation
    {

        private ViewFactory $view_factory;

        private MagicLink $magic_link;

        private UrlGenerator $url;

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